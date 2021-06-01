import tabula
import pandas as pd
import pymysql
import sys, getopt
import os
import logging


logging.basicConfig(filename="state-election-results.log", level=logging.DEBUG)


def process_file(filename):

  print("Processing ", filename)
  logging.info("Processing %s", filename)

  if(filename.find("ssembly") < 0):
    print("This script is for loading State Assembly only")
    print(filename)
    quit()

  metadata = filename.split("-")
  year = metadata[0]
  print(year)

  
  numbers = []
  j = 0
  for word in filename.split("-"):
    if(j == 0):
      j += 1
      continue
    #print(word)
    c = 0
    while(c <  len(word)):
      #print(char)
      if word[c].isnumeric():
        numbers.append(int(word[c]))
      c += 1		
  
  district = ""
  for n in numbers:
    district += str(n)
	
  if(len(district) < 2):
    district = "0"+district

  district = "LD"+district
	
  print("District", district)
  
  df1 = tabula.read_pdf(filename, pages='all', lattice=True, multiple_tables=False, pandas_options={'header': 1})

  #print()
  #print(df1)

  i = 0
  
  for df in df1:

    #df.rename(columns={0: "Place"}, inplace=True)
	# df.rename doesn't work for me
	
    #print(df.columns)
	
    r = 0
    d = 0
    dems = []
    reps = []

    new_cols = []
    new_cols.append("Place")
	
    for c in df.columns:
      #print(c)
      cc = c.split("\r")
      for a in cc:
        #print(a)
        a.replace(" ", "")
        if("democratic" in a.lower()):
          d += 1
          if(len(cc) > 2):
            dems.append(cc[0].replace(" ", "") + " " + cc[1].replace(" ", ""))		  
          else:		  
            dems.append(cc[0]) # hard to remove spaces in name but not between first and last name!
          #df.rename(columns={i: "Dem"+str(d)}, inplace=True)
          new_cols.append("Dem"+str(d))
        elif("republican" in a.lower()):
          r += 1
          if(len(cc) > 2):
            reps.append(cc[0].replace(" ", "") + " " + cc[1].replace(" ", ""))
          else:
            reps.append(cc[0])
          #df.rename(columns={i: "Rep"+str(r)}, inplace=True)
          new_cols.append("Rep"+str(r))
      i += 1

    while(len(df.columns) > len(new_cols)):
      new_cols.append("")
    
    df.columns = new_cols

    print(df.columns)
    print("Dems", dems)
    print("Reps", reps)	
	
    df.replace(',','', regex=True, inplace=True)
    df.fillna(-1).astype(int, errors="ignore")
	
    #print(df)

    tot_dem_1 = 0;
    tot_rep_1 = 0;
    tot_dem_2 = 0;
    tot_rep_2 = 0;

	
    county = ""
  
    i = 0
	
    for row in df.itertuples(index=False):
      if(pd.isna(row[2]) and len(row) > 3 and pd.isna(row[3])):
        if(isinstance(row[0], str)):
          county = row[0].title()
        continue
		
      if(pd.isna(row[0])):
        continue

      if(not pd.isna(row[0]) and "total" in row[0].lower()):
        continue
      
      #print(row)

      state = "NJ"
  
      office = "Assembly 1"
      dem_votes = None
      dem_cand = None
      if("Dem1" in df.columns):
        dem_votes = int(row.Dem1)
        dem_cand = dems[0]
        		
      rep_votes = None
      rep_cand = None	  # do the same for Reps
      if("Rep1" in df.columns):
        rep_votes = int(row.Rep1)
        rep_cand = reps[0]

      print("Insert into...", state, county, row[0], year, office, district, dem_votes, rep_votes, dem_cand, rep_cand)
      cursor.execute(sql, (state, county, row[0], year, office, district, dem_votes,rep_votes, dem_cand, rep_cand))
      if("Dem1" in df.columns):	  
        tot_dem_1 += dem_votes
      if("Rep1" in df.columns):	 
        tot_rep_1 += rep_votes
      i += 1

      office = "Assembly 2"
      dem_votes = None
      dem_cand = None
      if("Dem2" in df.columns):
        dem_votes = int(row.Dem2)
        dem_cand = dems[1]
		
      rep_votes = None
      rep_cand = None
      if("Rep2" in df.columns):
        rep_votes = int(row.Rep2)
        rep_cand = reps[1]
      print("Insert into...", state, county, row[0], year, office, district, dem_votes, rep_votes, dem_cand, rep_cand)
      cursor.execute(sql, (state, county, row[0], year, office, district, dem_votes,rep_votes, dem_cand, rep_cand))	  
      if("Dem2" in df.columns):	  
        tot_dem_2 += dem_votes
      if("Rep2" in df.columns):	  
        tot_rep_2 += rep_votes

      i += 1

  print("Tot Dem1", tot_dem_1, ", Rep1", tot_rep_1, ", Dem2", tot_dem_2, ", Rep2", tot_rep_2)
  logging.info("Tot Dem1 %s, Rep1 %s, Dem2 %s, Rep2 %s", tot_dem_1, tot_rep_1, tot_dem_2, tot_rep_2)
  
  print("Inserted " + str(i) + " rows")
  logging.info("%s inserted %s rows", filename, i)
  
  return i


# Read the database credentials from environmental variables
db_host = os.environ.get('DB_HOST')
db_user = os.environ.get('DB_USER')
db_pwd = os.environ.get('DB_PWD')
db_db = os.environ.get('DB_DB')

db_host = "giovannisce.net"
db_user = "giova_giova"
db_pwd = "Cristina!70"
db_db = "giova_nj_cd_7"
connection = pymysql.connect(host=db_host, user=db_user, password=db_pwd, db=db_db)

sql = "INSERT INTO `state_election_results_test` (`state`,`county`,`muni`,`year`,`office`,`district`,`dem_votes`,`rep_votes`,`dem_candidate`,`rep_candidate`) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)"

cursor = connection.cursor()

i = 0

arg1 = None

if(len(sys.argv) > 1):
  arg1 = sys.argv[1]
dir = None

if(arg1):
  if("pdf" in arg1):
    filename = sys.argv[1]
  else: # files in a sub-directory
    dir = sys.argv[1]
    print("Processing files in ", dir)
else: # files in this directory
  dir = os.getcwd()
  print("Processing files in ", dir)


if(dir):
  files = [f for f in os.listdir(dir) if os.path.isfile(f)]
  for f in files:
    if(f.endswith(".pdf")):
      #print("File to process: ", f)
      try:
        i += process_file(f)
      except Exception as ex:
        print("ERROR - Unable to process", f)
        print(ex)
        logging.error("ERROR - Unable to process %s %s", f, ex)
else:
  i += process_file(filename)

connection.commit()

connection.close()

print("Inserted " + str(i) + " rows")
logging.info("Inserted %s rows", i)