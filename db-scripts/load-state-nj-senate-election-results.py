import tabula
import pandas as pd
import pymysql
import sys, getopt
import os

# Wonder if can automate to handle the publish_flag (set this run to Y, set to N previous one?)


filename = sys.argv[1]

if(filename.find("us-house") <= 0  and filename.find("state-senate") <= 0):
  print("This script is for loading Federal Congress and State Senate only")
  print(filename)
  quit()
  
print("Processing ", filename)
  
metadata = filename.split("-")
year = metadata[0]
print(year)
office = metadata[5]
print(office)
district = metadata[6]
print(district)

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

sql = "INSERT INTO `state_election_results` (`county`,`muni`,`year`,`office`,`district`,`dem_votes`,`rep_votes`) VALUES (%s,%s,%s,%s,%s,%s,%s)"

cursor = connection.cursor()

df1 = tabula.read_pdf(filename, pages='all') #, pandas_options={'header': None})

print()
#print(df1)

i = 0
tot_dem = 0;
tot_rep = 0;

for df in df1:
  #df = df1[0]

  # there is more than on dictionary...!?

  # to find out the order of the columns from the pdf
  dem_first = False
  # very strange, this below gets just candidates name and party, good enough!
  #for key in df.keys():
    #print(key)
  if(df.keys()[1].find("Democratic") > 0):
    dem_first = True

  #print(df.columns)
  num_cols = len(df.columns)
  
  # there can be more than 2 parties, need to fix, first this, then all columns based extraction as well as DB
  # in DB just add "other"?
  # or skip for now and load only Dem & Rep?
  # columns order is not always the same!
  cols = []
  cols.append("Place")
  if(dem_first):
    cols.append("Democratic")
    cols.append("Republican")
  else:
    cols.append("Republican")
    cols.append("Democratic")

  while(len(cols) < num_cols):
    cols.append("")
  df.columns = cols
  # end managing the columns
  # ATTENTION, this probably doesn't work for Assembly (which has at least 4 columns)!!
  
  #print(df)

  df.replace(',','', regex=True, inplace=True)
  df['Democratic'] = df['Democratic'].fillna(-1).astype(int)
  df['Republican'] = df['Republican'].fillna(-1).astype(int)

  #print(df)

  district = district.split(".")[0]
  dist = district[len("district")]
  if(len(dist) < 2):
    dist = "0"+dist

  if(filename.find("state-senate")):
    dist = "LD"+dist

  dist = "NJ"+dist

  # Should be able to check that data has not already been loaded checking in DB by year+district
  
  #print()
  #print("SQL")
  #print()
  county = ""
  dem_votes = 0
  rep_votes = 0
  
  for row in df.itertuples(index=False):
    #print(row)
    if(row[0] == "nan"):
      continue
    if(isinstance(row[0], str) and row[0].find("County") > 0):
      county = row[0].split(" ")[0]
      continue

    if(isinstance(row[0], str) and row[0].find("Totals") > 0):
      continue

    if(row[1] < 0):
      continue

    if(dem_first):
      dem_votes = row[1]
      rep_votes = row[2]
    else:
      rep_votes = row[1]
      dem_votes = row[2]	

    print("Insert into...", county, row[0], year, office, dist, dem_votes, rep_votes)

    tot_dem += dem_votes
    tot_rep += rep_votes

    #cursor.execute(sql, (county,row[0],year,office,dist,row[1],row[2]))
    i += 1

#connection.commit()

connection.close()

print("Inserted " + str(i) + " rows")
  
print("Tot Dem ", tot_dem, " Tot Rep ", tot_rep)
