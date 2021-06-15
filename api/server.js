var express = require('express');
var app = express();
var fs = require("fs");
var mysql = require('mysql')
var cors = require('cors')
var Q = require('q');

const bodyParser = require('body-parser');

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

app.use(bodyParser.json());
app.use(bodyParser.urlencoded());  // for local testing

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
		  + " address, town, zip, state, email, facebook, govtrack, "
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
		  + " address, town, zip, state, email, facebook, govtrack, "
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

app.get('/representatives/counties', cors(), function (req, res) {
	query = "select county, first_elected, last_elected, expire_on, term, office, name, party, "
		  + " address, town, zip, state, email, facebook, govtrack, "
		  + " website, votesmart, propublica, opensecret, twitter, notes"
		  + " from representatives"
		  + " where ifnull(county,'') <> ''"
		  + " and expire_on >= year(now())"
	      + " order by county, office, expire_on, name";
	
	console.log('query: ' + query);
	pool.query(query, function (err, rows, fields) {
	  if (err) throw err

	  res.type('json');
	  res.end(JSON.stringify(rows));	  
	})
})

app.get('/party/county-committees/members', cors(), function (req, res) {
	query = "select county, town, muni_id, ward, precinct, "
		  + " member_name, member_email, member_role, address, state, zip_code, "
		  + " election_year, vacant, notes"
		  + " from dem_committee_members where 1 = 1"
	
	county = req.query.county;
	muni = req.query.muni;

	if (county) {
		query += " and county = '" + county + "'";
	}
	if (muni) {
		query += " and town like '" + muni + "%'";
	}
	
	query += " order by county, town, ward, precinct, gender, member_name";
	
	console.log('query: ' + query);
	pool.query(query, function (err, rows, fields) {
	  if (err) throw err

	  res.type('json');
	  res.end(JSON.stringify(rows));	  
	})
})

app.get('/party/municipal-committees', cors(), function (req, res) {
	query = "select county, muni, muni_id, chair_name, chair_email, website, facebook, "
		  + " committee_email, committee_phone, bylaws, address, term_years, "
		  + " last_election, next_election, gender_enforced, notes"
		  + " from dem_committee where muni <> ''"
	      + " order by county";
	
	console.log('query: ' + query);
	pool.query(query, function (err, rows, fields) {
	  if (err) throw err

	  res.type('json');
	  res.end(JSON.stringify(rows));	  
	})
})

app.get('/party/county-committees', cors(), function (req, res) {
	query = "select county, chair_name, chair_email, website, facebook, "
		  + " committee_email, committee_phone, bylaws, address, term_years, "
		  + " last_election, next_election, gender_enforced, notes"
		  + " from dem_committee where muni = ''"
	      + " order by county";
	
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
		query += " where county = '" + county + "'";
	}
	query += " order by county, muni";
	
	console.log('query: ' + query);
	pool.query(query, function (err, rows, fields) {
	  if (err) throw err

	  res.type('json');
	  res.end(JSON.stringify(rows));	  
	})
})


app.post('/myinfo', cors(), function (req, res) {
	const body = req.body;
	console.log(body);
	
	my_info_query = "select distinct county, city, municipality, street_number, street_name, zip, ward, precinct, cd, ld from alpha_voter_list_state";
		
	county = body.county;
	municipality = body.municipality;
	street_number = body.street_number;
	street_name = body.street_name;
	first_name = body.first_name;
	last_name = body.last_name;
	date_of_birth = body.date_of_birth;

	if (county && municipality) {
		my_info_query += " where county = '" + body.county + "'"
			   + " and city = '" + body.municipality + "'";
	} else {
		console.log("ERROR: missing input parameters");
	}
	
	if (street_number && street_name) {
		my_info_query += " and street_number = '" + street_number + "'"
			   + " and street_name like '" + street_name + "%'";
	} else if(first_name && last_name && date_of_birth) {
		my_info_query += " and first_name = '" + first_name + "'"
			   + " and last_name = '" + last_name + "'"
			   + " and dob = '" + date_of_birth + "'"; // mm/dd/yyyy
	} else {
		console.log("ERROR: missing input parameters");
	}
	
	console.log('my_info_query: ' + my_info_query);

	
	pool.query(my_info_query, function (err, rows, fields) {
	  if (err) throw err

      // should check that I got back one row only
	  
	  representatives_query = "select county, ld, cd, office, name, party, first_elected, last_elected, expire_on, term" 
	         + " from representatives where"
			 + " ld='"+rows[0].ld+"' or cd='"+rows[0].cd+"' or county='"+rows[0].county+"'"
			 //+ " or (muni='"+rows[0].municipality+"' and precinct='"+rows[0].precinct+"')" // needs ward too if muni
			 + " order by sort_by";
			 
	  console.log(representatives_query);
	  
	  candidates_query = "select cd, ld, county, election_year, election_type, office, term, name, incumbent, first_elected, last_elected,"
			 + " party, slogan, address, town, zip, state, email, website, facebook, twitter, endorsements, endorse_link"
			 + " from candidates_new where election_year='2021' and ("
			 + " ld='LD"+rows[0].ld+"' or cd='"+rows[0].cd+"' or county='"+rows[0].county+"'"
			 + " or (muni='"+rows[0].municipality+"' and precinct='"+rows[0].precinct+"'))" // needs ward too if muni
			 + " order by sort_by";
	  
	  console.log('candidates_query: ' + candidates_query);
	  
	  function getRepresentatives(){
        var defered = Q.defer();
        pool.query(representatives_query, defered.makeNodeResolver());
        return defered.promise;
      }

      function getCandidates(){
        var defered = Q.defer();
        pool.query(candidates_query, defered.makeNodeResolver());
        return defered.promise;
      }

	  my_info = JSON.stringify(rows[0]);
	  my_info = my_info.replace("}","");
	  
      Q.all([getRepresentatives(),getCandidates()]).then(function(results){
		office_holders = ",\"my_office_holders\":" + JSON.stringify(results[0][0]);  
		candidates = ",\"my_candidates\":" + JSON.stringify(results[1][0]);  
		console.log(my_info+office_holders+candidates+"}");
		res.type('json');
	    res.end(my_info+office_holders+candidates+"}");
      });
	});
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
