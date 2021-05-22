var express = require('express');
var app = express();
var fs = require("fs");

var mysql = require('mysql')

const PORT = process.env.PORT || 8081;

const db_host = process.env.DB_HOST;
const db_user = process.env.DB_USER;
const db_pwd = process.env.DB_PWD;
const db_name = process.env.DB_NAME;
//console.log('Your database is %', db_name);


var ip = require("ip");
console.log(ip.address());

//connection.timeout = 0;

var pool = mysql.createPool({
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

app.get('getNJStateCandidates', function (req, res) {
	query = "select election_year, election_type, county, ld, office, name, party, incumbent, first_elected, address, town, zip, state, email, website, facebook, twitter, slogan, endorsements, endorse_link, endorse_tooltip from candidates_new where election_year='2021' and election_type='PRI' order by ld, county, office, party, slogan, name";
		
	console.log('query: ' + query);
	pool.query(query, function (err, rows, fields) {
	  if (err) throw err

	  //rows.forEach(element => console.log(element));
	  //console.log('Retrieved: ', rows[0].solution)
	  res.end(JSON.stringify(rows));	  
	})
})


var server = app.listen(PORT, function () {
   var host = server.address().address
   var port = server.address().port
   //connection.connect()
   console.log("App listening at http://%s:%s", host, port)
})

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
