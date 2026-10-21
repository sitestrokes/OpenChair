<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| Largest US cities, roughly by population — the instant, offline half of the
| city autocomplete (Openchair::cities). OpenStreetMap's Nominatim matches whole
| words, so "san an" finds nothing there; this list matches as you type.
| Anything not listed is still found by Nominatim once the name is complete.
| Format: 'City|ST'.
*/
$config['oc_us_cities'] = [
    'New York|NY', 'Los Angeles|CA', 'Chicago|IL', 'Houston|TX', 'Phoenix|AZ', 'Philadelphia|PA',
    'San Antonio|TX', 'San Diego|CA', 'Dallas|TX', 'Jacksonville|FL', 'Austin|TX', 'Fort Worth|TX',
    'San Jose|CA', 'Columbus|OH', 'Charlotte|NC', 'Indianapolis|IN', 'San Francisco|CA', 'Seattle|WA',
    'Denver|CO', 'Oklahoma City|OK', 'Nashville|TN', 'Washington|DC', 'El Paso|TX', 'Las Vegas|NV',
    'Boston|MA', 'Detroit|MI', 'Portland|OR', 'Louisville|KY', 'Memphis|TN', 'Baltimore|MD',
    'Milwaukee|WI', 'Albuquerque|NM', 'Tucson|AZ', 'Fresno|CA', 'Sacramento|CA', 'Mesa|AZ',
    'Atlanta|GA', 'Kansas City|MO', 'Colorado Springs|CO', 'Omaha|NE', 'Raleigh|NC', 'Miami|FL',
    'Virginia Beach|VA', 'Long Beach|CA', 'Oakland|CA', 'Minneapolis|MN', 'Bakersfield|CA', 'Tulsa|OK',
    'Tampa|FL', 'Arlington|TX', 'Wichita|KS', 'Aurora|CO', 'New Orleans|LA', 'Cleveland|OH',
    'Honolulu|HI', 'Anaheim|CA', 'Henderson|NV', 'Orlando|FL', 'Lexington|KY', 'Stockton|CA',
    'Riverside|CA', 'Corpus Christi|TX', 'Irvine|CA', 'Cincinnati|OH', 'Santa Ana|CA', 'Newark|NJ',
    'Saint Paul|MN', 'Pittsburgh|PA', 'Greensboro|NC', 'Durham|NC', 'Lincoln|NE', 'Jersey City|NJ',
    'Plano|TX', 'Anchorage|AK', 'North Las Vegas|NV', 'St. Louis|MO', 'Madison|WI', 'Chandler|AZ',
    'Gilbert|AZ', 'Reno|NV', 'Buffalo|NY', 'Chula Vista|CA', 'Fort Wayne|IN', 'Lubbock|TX',
    'Toledo|OH', 'St. Petersburg|FL', 'Laredo|TX', 'Irving|TX', 'Chesapeake|VA', 'Glendale|AZ',
    'Winston-Salem|NC', 'Port St. Lucie|FL', 'Scottsdale|AZ', 'Garland|TX', 'Boise|ID', 'Norfolk|VA',
    'Spokane|WA', 'Richmond|VA', 'Fremont|CA', 'Huntsville|AL', 'Frisco|TX', 'Cape Coral|FL',
    'Santa Clarita|CA', 'San Bernardino|CA', 'Tacoma|WA', 'Hialeah|FL', 'Baton Rouge|LA', 'Modesto|CA',
    'Fontana|CA', 'McKinney|TX', 'Moreno Valley|CA', 'Des Moines|IA', 'Fayetteville|NC', 'Salt Lake City|UT',
    'Yonkers|NY', 'Worcester|MA', 'Rochester|NY', 'Sioux Falls|SD', 'Little Rock|AR', 'Amarillo|TX',
    'Tallahassee|FL', 'Grand Prairie|TX', 'Columbus|GA', 'Augusta|GA', 'Peoria|AZ', 'Oxnard|CA',
    'Knoxville|TN', 'Overland Park|KS', 'Birmingham|AL', 'Grand Rapids|MI', 'Vancouver|WA', 'Montgomery|AL',
    'Huntington Beach|CA', 'Providence|RI', 'Brownsville|TX', 'Tempe|AZ', 'Akron|OH', 'Glendale|CA',
    'Chattanooga|TN', 'Fort Lauderdale|FL', 'Newport News|VA', 'Mobile|AL', 'Ontario|CA', 'Clarksville|TN',
    'Cary|NC', 'Elk Grove|CA', 'Shreveport|LA', 'Eugene|OR', 'Aurora|IL', 'Salem|OR', 'Santa Rosa|CA',
    'Rancho Cucamonga|CA', 'Pembroke Pines|FL', 'Fort Collins|CO', 'Springfield|MO', 'Oceanside|CA',
    'Garden Grove|CA', 'Lancaster|CA', 'Murfreesboro|TN', 'Palmdale|CA', 'Corona|CA', 'Killeen|TX',
    'Salinas|CA', 'Roseville|CA', 'Denton|TX', 'Surprise|AZ', 'Macon|GA', 'Paterson|NJ', 'Lakewood|CO',
    'Hayward|CA', 'Charleston|SC', 'Alexandria|VA', 'Hollywood|FL', 'Springfield|MA', 'Kansas City|KS',
    'Sunnyvale|CA', 'Bellevue|WA', 'Joliet|IL', 'Naperville|IL', 'Escondido|CA', 'Bridgeport|CT',
    'Savannah|GA', 'Olathe|KS', 'Mesquite|TX', 'Pasadena|TX', 'McAllen|TX', 'Rockford|IL', 'Gainesville|FL',
    'Syracuse|NY', 'Pomona|CA', 'Visalia|CA', 'Thornton|CO', 'Waco|TX', 'Jackson|MS', 'Columbia|SC',
    'Lakewood|NJ', 'Fullerton|CA', 'Torrance|CA', 'Victorville|CA', 'Midland|TX', 'Orange|CA',
    'Miramar|FL', 'Hampton|VA', 'Warren|MI', 'Stamford|CT', 'Cedar Rapids|IA', 'Elizabeth|NJ',
    'Palm Bay|FL', 'Dayton|OH', 'New Haven|CT', 'Coral Springs|FL', 'Meridian|ID', 'West Valley City|UT',
    'Pasadena|CA', 'Lewisville|TX', 'Kent|WA', 'Sterling Heights|MI', 'Fargo|ND', 'Carrollton|TX',
    'Santa Clara|CA', 'Round Rock|TX', 'Norman|OK', 'Columbia|MO', 'Abilene|TX', 'Athens|GA',
    'Pearland|TX', 'Clovis|CA', 'Topeka|KS', 'College Station|TX', 'Simi Valley|CA', 'Allentown|PA',
    'West Palm Beach|FL', 'Thousand Oaks|CA', 'Vallejo|CA', 'Wilmington|NC', 'Evansville|IN', 'Concord|CA',
    'Lafayette|LA', 'Hartford|CT', 'Ann Arbor|MI', 'Berkeley|CA', 'Independence|MO', 'Provo|UT',
    'Lansing|MI', 'Arvada|CO', 'Clearwater|FL', 'Lakeland|FL', 'Miami Gardens|FL', 'Boulder|CO',
    'Odessa|TX', 'Murrieta|CA', 'Temecula|CA', 'Beaumont|TX', 'Richardson|TX', 'Rochester|MN',
    'Billings|MT', 'Manchester|NH', 'Charleston|WV', 'Cambridge|MA', 'Sarasota|FL', 'Naples|FL',
    'Fort Myers|FL', 'Boca Raton|FL', 'Daytona Beach|FL', 'Kissimmee|FL', 'Pensacola|FL', 'Ocala|FL',
    'Brandon|FL', 'Spring Hill|FL', 'Plantation|FL', 'Sunrise|FL', 'Davie|FL', 'Deltona|FL',
    'Melbourne|FL', 'Largo|FL', 'Palm Coast|FL', 'Jupiter|FL', 'Delray Beach|FL', 'Doral|FL',
    'Greenville|SC', 'Asheville|NC', 'Myrtle Beach|SC', 'Scranton|PA', 'Harrisburg|PA', 'Lancaster|PA',
    'Albany|NY', 'Portland|ME', 'Burlington|VT', 'Wilmington|DE', 'Annapolis|MD', 'Hoboken|NJ',
];
