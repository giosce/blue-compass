import tabula
import pandas as pd
import pymysql
import sys, getopt
import os
import logging

# Wonder if can automate to handle the publish_flag (set this run to Y, set to N previous one?)

# make it accept
# nothing: read all pdf in local directory
# directory: read all pdf in sub-directory
# file: process the file

# This script loads the legislature (senate + assembly) files 

logging.basicConfig(filename="state-election-results.log", level=logging.DEBUG)


class Metadata:
  district = ""


def prep_metadata(fname):

  metadata = fname.split("-")
  year = metadata[0]
  #print(year)

  if(len(metadata) > 8):
    dist = metadata[9].split(".")[0]
  else:
    dist = metadata[6].split(".")[0]
  
  if(len(dist) > 2):
    dist = dist.lstrip("district")
  
  if(len(dist) < 2):
    dist = "0"+dist

  if(fname.find("ssembly")):
    dist = "LD"+dist

  #dist = "NJ"+dist
  #print(dist)
   
  Metadata.year = year
  Metadata.district = dist
  
  return Metadata

  
def process_file(filename):

  print("Processing ", filename)
  logging.info("Processing %s", filename)

  df1 = tabula.read_pdf(filename, pages='all', guess=True, pandas_options={'header': None})

  #print()
  #print(df1)
  
  i = 0

  tot_sen_dem = 0
  tot_sen_rep = 0
  tot_ass1_dem = 0
  tot_ass1_rep = 0
  tot_ass2_dem = 0
  tot_ass2_rep = 0
  
  f = 0
  for df in df1:
    print(f)
    print(df)
    print()
    print("Colums")
    print(df.columns)
    print()
    f += 1

    x = 0
    h = None
    while(x < len(df.columns) and x < 2):
      print(x, df.iloc[x, 0], df.iloc[x, 1], df.iloc[x, 2])
      if("Republican" in df.iloc[x, 1] or "Republican" in df.iloc[x, 2]
      or "Democrat" in df.iloc[x, 1] or "Democrat" in df.iloc[x, 2]):
        h = x		
      x += 1

    c = 0
    while(c < len(df.iloc[h])):
      df.iloc[h:c] = df.iloc[h:c].replace(" ", "")
      c += 1
    print("iloc", df.iloc[h])
    df.columns = df.iloc[h]
    print(df.columns)	
    continue
	
    k = 0
    dems = []
    reps = []
    for c in df.columns:
      c = c.replace(" ", "")
	  # skip nan?
      #print(c)
      if("Democratic" in c):
        dems.append(k)
      if("Republican" in c):
        reps.append(k)
      k += 1

    x = 1
    if(len(dems) == 0):
      while(x <= 10):
        x += 1
        #row = df.iloc(x,)	
        #print("Row", x, row[x])
        print(df.iloc[x, 1])
        if(not pd.isna(df.iloc[x, 1]) and ("Republican" in df.iloc[x, 1] or "Democratic" in df.iloc[x, 1])):
          c = 0
          d = 0
          while(c < 7):
            c += 1
            o = df.iloc[x, c]			
            print("AAA", o)
            if(pd.isna(o)):
              print("skip", o)
              d -= 1
              continue
            oo = o.split(" ")			  
            if(len(oo) > 1):
              print("PROBLEM")
              if("Republican" in oo[0]):
                reps.append(c)		  
              if("Democratic" in oo[0]):
                dems.append(c)		  
              #c += 1
              d = 1			  
              if("Republican" in oo[1]):
                reps.append(c+d)		  
              if("Democratic" in oo[1]):
                dems.append(c+d)		  
              continue			  
            if("Republican" in o):
              reps.append(c+d)		  
            if("Democratic" in o):
              dems.append(c+d)		  
          break
      #print("New header", df.[x])	  
	
    print("Dems: ", dems)
    print("Reps: ", reps)
    logging.info("Dems %s", dems)
    logging.info("Reps %s", reps)
    
    df.replace(',','', regex=True, inplace=True)
  
    md = prep_metadata(filename)
  
    #df.iloc[:,[1,2,3,4,5,6,7]] = df.iloc[:,[1,2,3,4,5,6,7]].fillna(-1).astype(int, errors="ignore")

    df.fillna(-1).astype(int, errors="ignore")
    df.replace("-", 0, inplace=True)
	
    #print(df)
    #print(df.head)

    #print(df.iloc[0])
    #print(df[1])
	
    # Should be able to check that data has not already been loaded checking in DB by year+district
      
    county = None
    tot_row = None
	
    for row in df.itertuples(index=False):
      #if(row[3] < 0 or (row[1] == -1 and row[2] == -1 and row[3] == -1 and row[4] == -1)):  
      if(row[1] == -1 and row[2] == -1 and row[3] == -1 and row[4] == -1):
        if(pd.isna(row[0]) == False):	  
          county = row[0].title()
        continue

      if(pd.isna(row[1]) and pd.isna(row[2]) and pd.isna(row[3]) and pd.isna(row[4])):
        if(pd.isna(row[0]) == False):
          county = row[0].title()
          county = county.split("(")[0]		
        continue

      if(pd.isna(row[0]) or pd.isna(row[1]) or "total" in row[0].lower()):
        if(not pd.isna(row[0]) and "district" in row[0].lower()):  
          tot_row = row
        continue
	  
      state = "NJ"
	  
      office = "NJ Senate"
      print("Insert into...", state, county, row[0], md.year, office, md.district, row[dems[0]], row[reps[0]])
      #logging.debug("Insert into...", state, county, row[0], md.year, office, md.district, row[dems[0]], row[reps[0]])
      #cursor.execute(sql, (state, county,row[0],md.year,office,md.district,row[dems[0]],row[reps[0]]))
      
      office = "Assembly 1"
      print("Insert into...", state, county, row[0], md.year, office, md.district, row[dems[1]], row[reps[1]])
      #cursor.execute(sql, (state, county,row[0],md.year,office,md.district,row[dems[1]],row[reps[1]]))
      
      office = "Assembly 2"
      print("Insert into...", state, county, row[0], md.year, office, md.district, row[dems[2]], row[reps[2]])
      #cursor.execute(sql, (state, county,row[0],md.year,office,md.district,row[dems[2]],row[reps[2]]))

      tot_sen_dem += int(row[dems[0]])
      tot_sen_rep += int(row[reps[0]])
      tot_ass1_dem += int(row[dems[1]])
      tot_ass1_rep += int(row[reps[1]])
      tot_ass2_dem += int(row[dems[2]])
      tot_ass2_rep += int(row[reps[2]])

      i += 1

  print(filename, " Processed ", i, " rows")
  print("Tot Sen Dem ", tot_sen_dem, " Tot Sen Rep ", tot_sen_rep)
  print("Tot Ass1 Dem ", tot_ass1_dem, " Tot Ass1 Rep ", tot_ass1_rep)
  print("Tot Ass2 Dem ", tot_ass2_dem, " Tot Ass2 Rep ", tot_ass2_rep)
  if(tot_row != None):
    print(tot_row)
    logging.info(tot_row)
    if(str(tot_sen_dem) not in tot_row):
      print("ERROR - numbers don't match!")
      logging.info("ERROR - numbers don't match! %s", filename)

  logging.info("%s Processed %s rows", filename, i)
  logging.info("Tot Sen Dem %s, Sen Rep %s, Ass1 Dem %s, Ass1 Rep %s, Ass2 Dem %s, Ass2 Rep %s",tot_sen_dem,tot_sen_rep,tot_ass1_dem,tot_ass1_rep,tot_ass2_dem,tot_ass2_rep)

  return i
	  
# END of Functions	  
  
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
  

#if(filename.find("us-house") <= 0  and filename.find("state-senate") <= 0):
#  print("This script is for loading Federal Congress and State Senate only")
#  print(filename)
#  quit()

# needs to break the whole script in functions!

# Read the database credentials from environmental variables
db_host = os.environ.get('DB_HOST')
db_user = os.environ.get('DB_USER')
db_pwd = os.environ.get('DB_PWD')
db_db = os.environ.get('DB_DB')

#db_host = "giovannisce.net"
db_user = "giova_giova"
db_pwd = "Cristina!70"
db_db = "giova_nj_cd_7"
connection = pymysql.connect(host=db_host, user=db_user, password=db_pwd, db=db_db)

sql = "INSERT INTO `state_election_results` (`state`,`county`,`muni`,`year`,`office`,`district`,`dem_votes`,`rep_votes`) VALUES (%s,%s,%s,%s,%s,%s,%s,%s)"

cursor = connection.cursor()

i = 0

if(dir):
  files = [f for f in os.listdir(dir) if os.path.isfile(f)]
  for f in files:
    if(f.endswith(".pdf")):
      #print("File to process: ", f)
      try:
        i += process_file(f)
      except:
        print("ERROR - Unable to process", f)
        logging.error("ERROR - Unable to process %s", f)
else:
  i += process_file(filename)

connection.commit()

connection.close()

print("Inserted " + str(i) + " rows")
logging.info("Inserted %s rows", i)
