const { faker } = require('@faker-js/faker');

// Set these variables
var multiplier = 2000;

// Optional tweaks
var userCount = 10 * multiplier;
var ipCount = 5 * multiplier;
var uuidCount = 100 * multiplier;
var companyCount = 10 * multiplier;
var urlCount = 5 * multiplier;
var browserCount = multiplier;

var result = "<?php\nnamespace Txtlog\\Includes;\n\nclass Testdata {\n";
var users = [];
var ips = [];
var uuids = [];
var companies = [];
var urls = [];
var browsers = [];

for(let i=0; i<userCount; i++)
  users.push(faker.internet.displayName());

for(let i=0; i<ipCount; i++)
  ips.push(faker.internet.ip());

for(let i=0; i<uuidCount; i++)
  uuids.push(faker.string.uuid());

for(let i=0; i<companyCount; i++)
  companies.push(faker.company.name());

for(let i=0; i<urlCount; i++)
  urls.push(faker.internet.url());

for(let i=0; i<browserCount; i++)
  browsers.push(faker.internet.userAgent());

result += implode('users', users);
result += implode('ips', ips);
result += implode('uuids', uuids);
result += implode('companies', companies);
result += implode('urls', urls);
result += implode('browsers', browsers);

function implode(name, arr) {
  for(let i=0; i< arr.length; i++) {
    // Escape quotes: O'Connell -> O\'Connell
    arr[i] = arr[i].replace(/'/g, "\\'");
  }
  return '  public static $' + name + " = ['" + arr.join("','") + "'];\n\n";
}

result += "}";
console.log(result);
