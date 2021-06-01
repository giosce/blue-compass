import tabula
import pandas as pd
import pymysql
import sys, getopt
import os
import logging


logging.basicConfig(filename="governor-election-results.log", level=logging.DEBUG)

def process_file(filename):

  if(filename.find("governor") <= 0):
    print("This script is for loading Governor election results only")
    print(filename)
    return 0

  print("Processing ", filename)
  logging.info("Processing %s", filename)  
  
  metadata = filename.split("-")
  year = metadata[0]
  office = metadata[4]
  office = office.title()
  county = metadata[5].split(".")[0]
  county = county.title()
  print(county, year, office)


  df_header = tabula.read_pdf(filename, pages='all', stream=True, pandas_options={'header': None})
  c = len(df_header[0].columns) - 1
  #print("columns", c)
  candidates = df_header[0][c]
  #print(candidates)

  df1 = tabula.read_pdf(filename, pages='all', lattice=True, guess=True, pandas_options={'header': None})

  #print(df1)

  #print()

  d = 0
  r = 0
  dem_cand = None
  rep_cand = None

  for cand in candidates:
    if(isinstance(cand, str) == False or cand.isnumeric() == True or pd.isna(cand) == True):
      continue
    if("Democratic" in cand):
      if(isinstance(candidates[d], str)):  # cases in which previous line was not candidate name
        dem_cand = candidates[d].split("-")[0]
      break
    d = d + 1

  for cand in candidates:
    if(isinstance(cand, str) == False or cand.isnumeric() == True or pd.isna(cand) == True):
      continue
    if("Republican" in cand):
      if(isinstance(candidates[r], str)):  # cases in which previous line was not candidate name
        rep_cand = candidates[r].split("-")[0]
      break
    r = r + 1

  #print("d", d, "r", r)
  
  dem_first = d < r

  #print("dem_first", dem_first)
  
  i = 0
  tot_dem = 0;
  tot_rep = 0;

  for df in df1:

    df.replace(',','', regex=True, inplace=True)
    df[1] = df[1].fillna(-1).astype(int)
    df[2] = df[2].fillna(-1).astype(int)

    #print(df)

    dem_votes = 0
    rep_votes = 0
  
    state = "NJ"
	
    for row in df.itertuples(index=False):
      #print(row)
      if(pd.isna(row[1]) or row[1] < 0):
        continue

      if("Total" in row):
        continue

      if(dem_first):
        dem_votes = row[1]
        rep_votes = row[2]
      else:
        rep_votes = row[1]
        dem_votes = row[2]	

      #print("Insert into...", county, row[0], year, office, dem_votes, rep_votes, dem_cand, rep_cand)

      tot_dem += dem_votes
      tot_rep += rep_votes

      cursor.execute(sql, (state, county, row[0], year, office, dem_votes, rep_votes, dem_cand, rep_cand))
	  
      i += 1
	
  connection.commit()

  print(county, " inserted " + str(i) + " rows")
  print("Tot Dem ", tot_dem, " Tot Rep ", tot_rep)
	
  logging.info("%s - inserted %i rows", county, i)
  logging.info("Tot Dem %i, Tot Rep %i", tot_dem, tot_rep)
    
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

sql = "INSERT INTO `state_election_results` (`state`,`county`,`muni`,`year`,`office`,`dem_votes`,`rep_votes`,`dem_candidate`,`rep_candidate`) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s)"

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

print("Inserted " + str(i) + " rows")
logging.info("Inserted %s rows", i)

connection.close()
