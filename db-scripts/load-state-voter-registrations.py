import tabula
import pandas as pd
import pymysql
import sys, getopt
import os
from dotenv import load_dotenv

load_dotenv()
DB_HOST = os.getenv('DB_HOST')
DB_USER = os.getenv('DB_USER')
DB_PWD = os.getenv('DB_PWD')
DB_NAME = os.getenv('DB_NAME')

print(DB_USER)

# It would be great to understand from pdf which year/month we are loading
# To decide what to put in "as_of" column
# Wonder if can automate to handle the publish_flag (set this run to Y, set to N previous one?)


#import PyPDF2
#pdf_file = open('test.pdf')
#read_pdf = PyPDF2.PdfFileReader(pdf_file)
#number_of_pages = read_pdf.getNumPages()
#page = read_pdf.getPage(0)
#page_content = page.extractText()
#print(page_content)

if(len(sys.argv) > 1):
  filename = sys.argv[1]

  
fname = filename.split("/")[9]
print("fname:", fname)
aa = fname.split("-")
year = aa[0]
month = aa[1][0:2]
day = aa[1][2:4]
if(day == ""):
  day = "01"
print("day:", day)

as_of = year + "-" + month + "-" + day 

print("As of:", as_of) 
print("Year Month:", year, month)

months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"]

dt_label = months[int(month)-1] + " " + year

print("Date Label:", dt_label)

#quit()

df1 = tabula.read_pdf(filename, pages=1, pandas_options={'header': None})

df = df1[0]

#df.columns=["dist", "una", "dem", "rep", "cnv", "con", "gre", "lib", "nat", "rfp", "ssp", "tot"]

#col_names=["dist", "una", "dem", "rep", "cnv", "con", "gre", "lib", "nat", "rfp", "ssp", "tot"]

#print(df)

df.replace(',','', regex=True, inplace=True)
#df['una'] = df['una'].astype(int)
#df['dem'] = df['dem'].astype(int)
#df['rep'] = df['rep'].astype(int)
#df['tot'] = df['tot'].astype(int)

print(df)

print("Cols", df.columns)

print("Iloc", df.iloc[0])

df.columns = df.iloc[0]

print("Cols", df.columns)

#df.dropna(1, 'all', inplace=True)

print(df)

#print("NAT", df.NAT)

#print(pd.isna(df.NAT))
#print(pd.isna(df.REP))

#print()
#print(df.NAT.isnull().sum())
#print(df.REP.isnull().sum())
#print(len(df))

#year = filename.split("-")[0]
#month = filename.split("-")[1][0:2]

#as_of = filename[0:9] #some files have only 2014-11
#aa = filename.split("-")[0:2] #some files have only 2014-11
#as_of = aa[0] + " " + aa[1] 


#connection = pymysql.connect(host='giovannisce.net', user='giova_giova', password='Cristina!70', db='giova_nj_cd_7')
connection = pymysql.connect(host=DB_HOST, user=DB_USER, password=DB_PWD, db=DB_NAME)

sql = "INSERT INTO `state_voter_registrations` (`year`,`month`,`dt_label`,`district`,`una`,`dem`,`rep`,`cnv`,`con`,`gre`,`lib`,`nat`,`rfp`,`ssp`,`as_of`) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)"

cursor = connection.cursor()

i = 0
for row in df.itertuples(index=False):
  #print(row)
  #print("una: ", row[col_names.index('una')])
  if(pd.isna(row[0]) or pd.isna(row[1])):
    continue

  if(type(row[0]) == 'str'):
    continue
  #b = isinstance(row[0], str)
  b = row[0].isnumeric()
  print(row[0], b)
  if(b == False):
    continue
  print(type(row[0]))	
  #if(type(row[0]) != 'int' and "total" in row[0].lower()):
  #if("otal" in row[0]):
  #  continue

  dist = str(row[0])
  if(len(dist) < 2):
    dist = "0"+dist
  if("congressional" in filename):
    dist = "CD"+dist
  if("legislative" in filename):
    dist = "LD"+dist
	
  #print("dist: ", dist)
  
  print("SQL Insert...", year,month,dt_label,dist,row[1],row[2],row[3],row[4],row[5],row[6],row[7],row[8],row[9],row[10],as_of)

  #cursor.execute(sql, (year,month,dt_label,dist,row[1],row[2],row[3],row[4],row[5],row[6],row[7],row[8],row[9],row[10],as_of))
  i += 1

connection.commit()

connection.close()

print("Inserted " + str(i) + " rows")
