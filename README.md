# blue-compass
Back-End to load and provide access to New Jersey electoral data.

The project started in 2017 and until 2020 it has been managed only by @giosce and therefore it didn't get very structured.
Eventually it is getting a little more structured, the main sections are becaming more stable.

Some of the code has evolved into more like a framework and some database structure has been simplified.

Unfortunately, given the way that electoral data is available in NJ, one interesting feature of the early day doesn't seem very doable and it is to handle data at precinct level.
Precincts are areas within a municipality. Handling data at precinct level would have allowed a desirable feature, the slicing of every election data across every judisdiction.
That means for example, to show accurate votes count of Congressional elections against Legislative districts or viceversa or other slicing. 
Using the much easier to handle municipality level data presents the challenge that some municipalities are split across districts and so it is difficult to apportion votes across different jurisdictions.
That can be a challenging next step.

The main stable sections are:
Voter Registrations and Election Results at Congressional and Legislative district level as well as Election Candidates and Representatives at Congressional, Legislative and County level.
The MyInfo section is a very useful feature, only drawback is that it is using 2019 addresses, more recent data should be uploaded.
The Party Committees section perhaps should become bipartisan, it is very challenging to get accurate data.
The "Election Information" section needs to be stabilized on which information to show and how.
One recent track of activity is to build exaustive public API so that anyone can access the underlying data.
