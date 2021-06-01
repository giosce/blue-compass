var express = require('express');
var app = express();
var fs = require("fs");
var mysql = require('mysql')
var cors = require('cors')

//const swaggerUi = require('swagger-ui-express')
//const swaggerFile = require('./swagger_output.json')

//require('dotenv').config();
// above gives an error in Heroku. To use .env locally run node -r dotenv/config your_script.js

const PORT = process.env.PORT || 8081;

const db_host = process.env.DB_HOST;
const db_user = process.env.DB_USER;
const db_pwd = process.env.DB_PWD;
const db_name = process.env.DB_NAME;
console.log('Your database is %', db_name);
console.log('Your database server is %', db_host);

var ip = require("ip");
console.log(ip.address());

//connection.timeout = 0;

var pool = mysql.createPool({debug: true,
  connectionLimit : 10,
  host: db_host,
  user: db_user,
  password: db_pwd,
  database: db_name
})

app.get('/districts', function (req, res) {
	query = 'select * from cd';
	
	cd = req.query.cd;
	console.log('cd: ' + cd);
	if (cd != undefined) {
		query += " where cd = '" + cd + "'";
	}
	console.log('query: ' + query);
	pool.query(query, function (err, rows, fields) {
	  if (err) throw err

	  //rows.forEach(element => console.log(element));
	  //console.log('Retrieved: ', rows[0].solution)
	  res.type('json');
	  res.end(JSON.stringify(rows));	  
	})
})

app.get('/congress/results', function (req, res) {
	query = "select year, district, sum(dem_votes) as dem_votes, sum(rep_votes) as rep_votes from state_election_results where district like 'CD%'";
	
	year = req.query.year;
	console.log('year: ' + year);
	if (year != undefined) {
		query += " and year = '" + year + "'";
	}
	query += " group by year, district order by year, district";
	
	console.log('query: ' + query);
	pool.query(query, function (err, rows, fields) {
	  if (err) throw err

	  //rows.forEach(element => console.log(element));
	  //console.log('Retrieved: ', rows[0].solution)
	  res.end(JSON.stringify(rows));	  
	})
})

app.get('/congress/turnout', function (req, res) {
	query = "select year, cd, sum(registered_voters) as registered_voters, sum(ballots_cast) as ballots_cast from state_ballots_cast a, municipal_list_new b where a.muni_code = b.muni_code";
	
	year = req.query.year;
	console.log('year: ' + year);
	if (year != undefined) {
		query += " and year = '" + year + "'";
	}
	query += " group by year, cd order by year, cd";
	
	console.log('query: ' + query);
	pool.query(query, function (err, rows, fields) {
	  if (err) throw err

	  //rows.forEach(element => console.log(element));
	  //console.log('Retrieved: ', rows[0].solution)
	  res.end(JSON.stringify(rows));	  
	})
})

app.get('/candidates/nj/2021/pri', function (req, res) {
	query = "select election_year, election_type, county, ld, office, name, party, incumbent, first_elected, address, town, zip, state, email, website, facebook, twitter, slogan, endorsements, endorse_link, endorse_tooltip from candidates_new where election_year='2021' and election_type='PRI' order by ld, county, office, party, slogan, name";
		
	console.log('query: ' + query);
	pool.query(query, function (err, rows, fields) {
	  if (err) throw err

	  //rows.forEach(element => console.log(element));
	  //console.log('Retrieved: ', rows[0].solution)
	  res.end(JSON.stringify(rows));	  
	})
})

app.get('/voter-registrations/congressional-districts', cors(), function (req, res) {
	query = "select * from state_voter_registrations where district like 'CD%'";
		
	year = req.query.year;
	console.log('year: ' + year);
	if (year != undefined) {
		query += " and year = '" + year + "'";
	}
	query += " order by district, year, month";
	
	console.log('query: ' + query);
	pool.query(query, function (err, rows, fields) {
	  if (err) throw err

	  res.type('json');
	  res.end(JSON.stringify(rows));	  
	})
})

app.get('/voter-registrations/legislative-districts', cors(), function (req, res) {
	query = "select * from state_voter_registrations where district like 'LD%'";
		
	year = req.query.year;
	console.log('year: ' + year);
	if (year != undefined) {
		query += " and year = '" + year + "'";
	}
	query += " order by district, year, month";
	
	console.log('query: ' + query);
	pool.query(query, function (err, rows, fields) {
	  if (err) throw err

	  res.type('json');
	  res.end(JSON.stringify(rows));	  
	})
})

app.get('/candidates/legislative-districts', cors(), function (req, res) {
	query =  "select ld, election_year, election_type, office, vote_for, term, name, party,";
	query += " incumbent, address, town, zip, state, email, website, facebook, twitter, first_elected,";
	query += " endorsements, last_elected, slogan";
	query += " from candidates_new where ifnull(ld, '') <> ''";
		
	year = req.query.year;
	type = req.query.type; // PRI or GEN

	if (year != undefined) {
		query += " and election_year = '" + year + "'";
	}
	if (type != undefined) {
		query += " and election_type = '" + type + "'";
	}
	query += " order by ld, election_year, election_type, office, party, slogan, name";
	
	console.log('query: ' + query);
	pool.query(query, function (err, rows, fields) {
	  if (err) throw err
	  
	  res.type('json');
	  res.end(JSON.stringify(rows));	  
	})
})

app.get('/candidates/congressional-districts', cors(), function (req, res) {
	query =  "select cd, election_year, election_type, office, vote_for, term, name, party,";
	query += " incumbent, address, town, zip, state, email, website, facebook, twitter, first_elected,";
	query += " endorsements, last_elected, slogan";
	query += " from candidates_new where ifnull(cd, '') <> ''";
		
	year = req.query.year;
	type = req.query.type; // PRI or GEN

	if (year != undefined) {
		query += " and election_year = '" + year + "'";
	}
	if (type != undefined) {
		query += " and election_type = '" + type + "'";
	}
	query += " order by cd, election_year, election_type, office, party, slogan, name";
	
	console.log('query: ' + query);
	pool.query(query, function (err, rows, fields) {
	  if (err) throw err

	  //rows.forEach(element => console.log(element));
	  //console.log('Retrieved: ', rows[0].solution)
	  res.type('json');
	  res.end(JSON.stringify(rows));	  
	})
})

app.get('/candidates/counties', cors(), function (req, res) {
	query =  "select county, election_year, election_type, office, vote_for, term, name, party,";
	query += " incumbent, address, town, zip, state, email, website, facebook, twitter, first_elected,";
	query += " endorsements, last_elected, slogan";
	query += " from candidates_new where ifnull(county,'') <> ''";
		
	year = req.query.year;
	console.log('year: ' + year);
	if (year != undefined) {
		query += " and election_year = '" + year + "'";
	}
	query += " order by county, election_year, election_type, office, party, slogan, name";
	
	console.log('query: ' + query);
	pool.query(query, function (err, rows, fields) {
	  if (err) throw err

	  //rows.forEach(element => console.log(element));
	  //console.log('Retrieved: ', rows[0].solution)
	  res.type('json');
	  res.end(JSON.stringify(rows));	  
	})
})

// Governor, Federal Senators, President
app.get('/candidates/statewide', cors(), function (req, res) {
	query =  "select 'statewide', election_year, election_type, office, vote_for, term, name, party,";
	query += " incumbent, address, town, zip, state, email, website, facebook, twitter, first_elected,";
	query += " endorsements, last_elected, slogan";
	query += " from candidates_new where office in ('Governor','President','US Senate')";
		
	year = req.query.year;
	console.log('year: ' + year);
	if (year != undefined) {
		query += " and election_year = '" + year + "'";
	}
	query += " order by election_year, election_type, office, party, slogan, name";
	
	console.log('query: ' + query);
	pool.query(query, function (err, rows, fields) {
	  if (err) throw err

	  //rows.forEach(element => console.log(element));
	  //console.log('Retrieved: ', rows[0].solution)
	  res.type('json');
	  res.end(JSON.stringify(rows));	  
	})
})

// Only General Elections
app.get('/election-results/legislative-districts', cors(), function (req, res) {
	query = "select year, district, office, sum(dem_votes) as dem_votes, sum(rep_votes) as rep_votes"
		  + " from state_election_results"
		  + " where (office like 'Assembly%' or office='NJ Senate')";
		
	year = req.query.year;

	if (year != undefined) {
		query += " and year = '" + year + "'";
	}
	
	query += " group by year, district, office"
	       + " order by year, district, office";
	
	console.log('query: ' + query);
	pool.query(query, function (err, rows, fields) {
	  if (err) throw err

	  res.type('json');
	  res.end(JSON.stringify(rows));	  
	})
})

// Only General Elections
app.get('/election-results/congressional-districts', cors(), function (req, res) {
	query = "select year, district, office, sum(dem_votes) as dem_votes, sum(rep_votes) as rep_votes"
		  + " from state_election_results"
		  + " where office = 'US House'";
		
	year = req.query.year;

	if (year != undefined) {
		query += " and year = '" + year + "'";
	}
	
	query += " group by year, district"
	       + " order by year, district";
	
	console.log('query: ' + query);
	pool.query(query, function (err, rows, fields) {
	  if (err) throw err
	  
	  res.type('json');
	  res.end(JSON.stringify(rows));	  
	})
})

// Only General Elections
app.get('/election-results/statewide', cors(), function (req, res) {
	query = "select year, office, sum(dem_votes) as dem_votes, sum(rep_votes) as rep_votes"
		  + " from state_election_results"
		  + " where office in ('President', 'Governor', 'US Senate')";
		
	year = req.query.year;

	if (year != undefined) {
		query += " and year = '" + year + "'";
	}
	
	query += " group by year, office"
	       + " order by year, office";
	
	console.log('query: ' + query);
	pool.query(query, function (err, rows, fields) {
	  if (err) throw err

	  res.type('json');
	  res.end(JSON.stringify(rows));	  
	})
})

app.get('/representatives/congressional-districts', cors(), function (req, res) {
	query = "select cd, first_elected, last_elected, expire_on, term, office, name, party, "
		  + " website, votesmart, propublica, opensecret, twitter, notes"
		  + " from representatives"
		  + " where ifnull(cd,'') <> ''"
	      + " order by cd";
	
	console.log('query: ' + query);
	pool.query(query, function (err, rows, fields) {
	  if (err) throw err

	  res.type('json');
	  res.end(JSON.stringify(rows));	  
	})
})

app.get('/representatives/legislative-districts', cors(), function (req, res) {
	query = "select ld, first_elected, last_elected, expire_on, term, office, name, party, "
		  + " website, votesmart, propublica, opensecret, twitter, notes"
		  + " from representatives"
		  + " where ifnull(ld,'') <> ''"
	      + " order by ld";
	
	console.log('query: ' + query);
	pool.query(query, function (err, rows, fields) {
	  if (err) throw err

	  res.type('json');
	  res.end(JSON.stringify(rows));	  
	})
})


app.get('/state/nj/legislature/candidates', function (req, res) {
	query = "select election_year, election_type, county, ld, office, name, party, incumbent, first_elected, address, town, zip, state, email, website, facebook, twitter, slogan, endorsements, endorse_link, endorse_tooltip from candidates_new where election_year='2021' and election_type='PRI' order by ld, county, office, party, slogan, name";
		
	console.log('query: ' + query);
	pool.query(query, function (err, rows, fields) {
	  if (err) throw err

	  //rows.forEach(element => console.log(element));
	  //console.log('Retrieved: ', rows[0].solution)
	  res.end(JSON.stringify(rows));	  
	})
})

app.get('/state/nj/state/candidates', function (req, res) {
	query = "select election_year, election_type, county, ld, office, name, party, incumbent, first_elected," 
		  + "address, town, zip, state, email, website, facebook, twitter, slogan, endorsements, endorse_link,"
		  + "endorse_tooltip from candidates_new "
		  + "where election_year='2021' and election_type='PRI' "
		  + "order by ld, county, office, party, slogan, name";
		
	console.log('query: ' + query);
	pool.query(query, function (err, rows, fields) {
	  if (err) throw err

	  //rows.forEach(element => console.log(element));
	  //console.log('Retrieved: ', rows[0].solution)
	  res.end(JSON.stringify(rows));	  
	})
})

app.get('/state/nj/legislature/candidates', function (req, res) {
	query = "select election_year, election_type, county, ld, office, name, party, incumbent, first_elected, address, town, zip, state, email, website, facebook, twitter, slogan, endorsements, endorse_link, endorse_tooltip from candidates_new where election_year='2021' and election_type='PRI' order by ld, county, office, party, slogan, name";
		
	console.log('query: ' + query);
	pool.query(query, function (err, rows, fields) {
	  if (err) throw err

	  //rows.forEach(element => console.log(element));
	  //console.log('Retrieved: ', rows[0].solution)
	  res.end(JSON.stringify(rows));	  
	})
})

app.get('/counties', cors(), function (req, res) {
	query = "select distinct county from municipal_list_new order by 1";
		
	console.log('query: ' + query);
	pool.query(query, function (err, rows, fields) {
	  if (err) throw err

	  res.type('json');
	  res.end(JSON.stringify(rows));	  
	})
})

app.get('/municipalities', cors(), function (req, res) {
	query = "select county, muni_id, muni, cd, ld from municipal_list_new";
		
	county = req.query.county;

	if (county != undefined) {
		query += " county = '" + county + "'";
	}
	query += " order by county, muni";
	
	console.log('query: ' + query);
	pool.query(query, function (err, rows, fields) {
	  if (err) throw err

	  res.type('json');
	  res.end(JSON.stringify(rows));	  
	})
})


var server = app.listen(PORT, function () {
   var host = server.address().address
   var port = server.address().port
   //connection.connect()
   console.log("App listening at http://%s:%s", host, port)
})

app.use(cors())

//app.use('/doc', swaggerUi.serve, swaggerUi.setup(swaggerFile))

// when shutdown signal is received, do graceful shutdown
/*
process.on( 'SIGINT', function(){
  http_instance.close( function(){
    console.log( 'gracefully shutting down :)' );
	connection.end()
    process.exit();
  });
});
*/
