import tabula
import pandas as pd
import pymysql
import sys, getopt
import os
import minecart
import PyPDF2

# It accepts
# nothing: read all pdf in local directory
# directory: read all pdf in sub-directory
# file: process the file


def process_file(filename):

  print("Processing ", filename)

  if(filename.find("ballotscast") <= 0):
    print(filename)
    print("This script is for loading ballots cast files only")
    return 0
  
  df1 = tabula.read_pdf(filename, pages='all', lattice=True) #, pandas_options={'header': None})

  #print()
  #print(df1)

  year = filename.split("-")[0]
  county = filename.split("-")[-1].split(".")[0]
  
  i = 0
  tot_reg = 0
  tot_cast = 0
  
  for df in df1:

    #for key in df.keys():
      #print(key)

    #print(df)

    # Should be able to check that data has not already been loaded checking in DB by year+district
  
    df.replace(',','', regex=True, inplace=True)
    df.replace('%','', regex=True, inplace=True)
    
    df.iloc[:,[1,2,3,4,5]] = df.iloc[:,[1,2,3,4,5]].fillna(0).astype(int)

    month = "November" # try to get it from header of file, maybe the whole date
	
    for row in df.itertuples(index=False):
      #print(row)

      if(isinstance(row[0], str) and "total" in row[0].lower()):
        continue
      if(pd.isna(row[0])):
        continue

      print("Insert into...", county, row[0], year, month, row[1], row[2], row[3], row[4], row[5])

      tot_reg += row[1]
      tot_cast += row[2]

      cursor.execute(sql, (county,row[0],year,month,row[1],row[2],row[3],row[4],row[5]))
      i += 1

  print(filename, " Processed ", i, " rows")
  print("Tot Registered ", tot_reg, " Tot Ballots Cast ", tot_cast)

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

sql = "INSERT INTO `state_ballots_cast` (`county`,`muni`,`year`,`month`,`registered_voters`,`ballots_cast`,`pct_ballots_cast`,`mail_ballots_cast`,`provisional_ballots_cast`)" 
sql += "VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s)"

cursor = connection.cursor()

i = 0

if(dir):
  files = [f for f in os.listdir(dir) if os.path.isfile(f)]
  for f in files:
    if(f.endswith(".pdf")):
      #print("File to process: ", f)
      i += process_file(f)
else:
  i += process_file(filename)

connection.commit()

connection.close()

print("Inserted " + str(i) + " rows")
