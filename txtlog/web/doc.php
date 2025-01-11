<?php
use Txtlog\Controller\Account;
use Txtlog\Controller\Settings;
use Txtlog\Core\Constants;
use Txtlog\Includes\Token;

$hostname = (new Settings)->get()->getSitename();
$url = "https://$hostname";
$logDomain = (new Settings)->get()->getLogDomain();
$defaultAccount = (new Account)->get((new Settings)->get()->getAnonymousAccountID());
?>
<div class="container">
  <section class="section">
    <div class="title">
      Documentation
    </div>

    <div class="block">
      Txtlog offers a simple online logfile. The REST API can be used to create and manage logs.<br>
      Setting up a <a href="/selfhost">self hosted</a> version is described on a separate page.<br>
      To start, create a new log. Each log can contain zero or more rows.<br>
      The return value are for successful actions, the error table at the bottom lists the potential errors which can occur.
    </div>
  </section>

  <section class="section">
    <div class="title">
      Insert new logs
    </div>

    <div class="block">
      Send a POST request to create a new log row. Data is inserted asynchronously in batches, usually within a few seconds.<br>
      Send unformatted text or JSON. It's possible to insert up to 10.000 rows at once, see the examples below.<br>
      All fields are optional and data is automatically truncated if a row is too long.
    </div>
    <div class="block">
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-success is-light">
            Domain
          </span>
        </div>
        <div class="column">
          <?=$logDomain?>
        </div>
      </div>
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-success is-light">
            Endpoint
          </span>
        </div>
        <div class="column">
          /api/log
        </div>
      </div>
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-success is-light">
            Method
          </span>
        </div>
        <div class="column">
          POST
        </div>
      </div>
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-success is-light">
            Return value
          </span>
        </div>
        <div class="column">
          HTTP status code 202 (Accepted).<br>
          Number of inserted rows.
          <pre class="doc">
{
  "status": "accepted",
  "inserts": 1
}</pre>
        </div>
      </div>
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-warning is-light">
            Header: authorization
          </span>
        </div>
        <div class="column">
          Required, use an insert or admin token to insert new rows.
        </div>
      </div>
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-warning is-light">
            Header: X-Metadata
          </span>
        </div>
        <div class="column">
          Optional, set to false to disable automatic metadata.
        </div>
      </div>
      <div class="block">
        If the input is JSON, the log is extended with a metadata field <em>txtlog_metadata</em>, if it does not exist.<br>
        The metadata contains a field <em>origin_ip</em> which is filled with the IP address which inserted the log.<br>
        If the input JSON contains a field <em>ip</em> the metadata includes a GEO IP check on this address and sets the fields <em>ip_country</em>, <em>ip_provider</em> and <em>ip_tor</em> (true if the IP address is a TOR exit node).<br>
        If the input JSON contains a field <em>browser</em> the metadata is extended with the fields <em>browser_name</em>, <em>browser_version</em> and <em>browser_os</em>.<br>
      </div>
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-info is-light">
            date
          </span>
        </div>
        <div class="column">
          Custom event date, use this date to specify the exact time an event happened. This value can be at most one day in the past.<br>
          The date column must have exactly this format: yyyy-mm-dd hh:mm:ss or yyyy-mm-dd hh:mm:ss.sss to indicate milliseconds.<br>
          Indicators, such as <em>Z</em> or <em>T</em> are ignored, the date field does not handle timezones, use your local time when inserting logs. Some valid dates:<br>
          2024-02-17 09:30:00<br>
          2024-01-28 22:01:13.810<br>
          2024-01-28T22:01:13.810Z<br>
        </div>
      </div>
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-info is-light">
            ip
          </span>
        </div>
        <div class="column">
          If the field <em>ip</em> contains a valid IP address, a Geo IP check is performed. The log message is extended with the IP country, provider and a boolean indicating if the IP is a TOR address.
        </div>
      </div>
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-info is-light">
            browser
          </span>
        </div>
        <div class="column">
          If the field <em>browser</em> contains a valid User Agent string, this value is parsed and the log is extended with browser metadata.
        </div>
      </div>
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-info is-light">
            rows
          </span>
        </div>
        <div class="column">
          Multiple rows can be provided in one request.<br>
          Use a JSON array to specify multiple rows, see the example below.
        </div>
      </div>
    </div>
    <div class="tabs examples mb-0">
      <ul>
        <li class="tabcurl is-active" data-example="curl"><a>Curl</a></li>
        <li class="tabc" data-example="c"><a>C#</a></li>
        <li class="tabnode" data-example="node"><a>Node.js</a></li>
        <li class="tabphp" data-example="php"><a>PHP</a></li>
        <li class="tabpython" data-example="python"><a>Python</a></li>
      </ul>
    </div>
    <div class="block example curl-example">
      Add a new row using cURL.
    </div>
    <pre class="doc example curl-example">
curl <?=$logDomain?>/api/log \
-H "Authorization: ncSZX8Qz8Kqzj9Kbk4F6HR22xAF3" \
-d '{"date":"2024-11-03 11:13:01","msg":"login test","ip":"127.0.0.1","browser":"Mozilla/5.0 (X11; Linux x86_64; rv:128.0) Gecko/20100101 Firefox/128.0"}'
    </pre>
    <div class="block example curl-example">
      It is faster to insert multiple rows at once. Use the <em>rows</em> element to specify multiple rows.
    </div>
    <pre class="doc example curl-example">
curl <?=$logDomain?>/api/log \
-H "Authorization: ncSZX8Qz8Kqzj9Kbk4F6HR22xAF3" \
-d '{"rows":[{"action":"rdplogin","user":"admin"},{"action":"sshlogout","ip":"127.0.0.1"}]}'
    </pre>
    <pre class="doc example c-example">
using System;
using System.Net.Http;

class Txtlog {
  public async System.Threading.Tasks.Task Send() {
    // Add a new row to a log, set these three variables
    string logUrl = "<?=$logDomain?>/api/log";
    string authorization = "ncSZX8Qz8Kqzj9Kbk4F6HR22xAF3";
    var data = new System.Collections.Generic.Dictionary&lt;string, string&gt;() { { "user", "1234" }, { "trace_id", Guid.NewGuid().ToString() } };

    using (var client = new HttpClient()) {
      client.DefaultRequestHeaders.TryAddWithoutValidation("Authorization", authorization);
      var jsonSerializer = new System.Web.Script.Serialization.JavaScriptSerializer();
      HttpResponseMessage response = await client.PostAsync(logUrl, new StringContent(jsonSerializer.Serialize(data)));

      string result = await response.Content.ReadAsStringAsync();
      Console.WriteLine(result);
    }
  }
}

class Program {
  static void Main(string[] args) {
    Txtlog t = new Txtlog();
    t.Send().Wait();
  }
}</pre>
    <pre class="doc example node-example">
// Add a new row to a log, set these variables
authorization = 'ncSZX8Qz8Kqzj9Kbk4F6HR22xAF3';
data = { "user": 456, "login": true};

const https = require('node:https');

const options = {
  hostname: '<?=substr($logDomain, 8)?>',
  path: '/api/log',
  method: 'POST',
  headers: {
    'Authorization': authorization,
 }
};

const req = https.request(options, (res) =&gt; {
  res.on('data', (result) =&gt; {
    process.stdout.write(result);
  });
});

req.write(JSON.stringify(data));
req.end();</pre>
    <pre class="doc example php-example">
&lt;?php
// Add a new row to a log, set these variables
$authorization = 'ncSZX8Qz8Kqzj9Kbk4F6HR22xAF3';
$data = ['user'=&gt;123, 'ip'=&gt;'127.0.0.1'];

$logUrl = '<?=$logDomain?>/api/log';
$ch = curl_init($logUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
if(strlen($authorization) &gt; 0) {
  curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: $authorization"]);
}
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
$result = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
if($result === false || $status != 202) {
  // Handle errors
}
curl_close($ch);

print_r($result);</pre>
    <pre class="doc example python-example">
import requests

# Add a new row to a log, set these three variables
url = "<?=$logDomain?>/api/log"
data = {'user': 123, 'type': 'login'}
authorization = "ncSZX8Qz8Kqzj9Kbk4F6HR22xAF3"

headers = {
  'Authorization': authorization
}

result = requests.post(url, headers=headers, json=data)
print(result.text)</pre>
  </section>

  <section class="section">
    <div class="title">
      Create new log
    </div>

    <div class="block">
      <div class="block">
        Create a new log, all parameters are optional.
      </div>
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-success is-light">
            Endpoint
          </span>
        </div>
        <div class="column">
          /api/log
        </div>
      </div>
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-success is-light">
            Method
          </span>
        </div>
        <div class="column">
          POST
        </div>
      </div>
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-success is-light">
            Return value
          </span>
        </div>
        <div class="column content">
          HTTP status code 201 (Created).<br>
          JSON object containing several tokens and an URL to view the log.
          <ul>
            <li>admin: token to insert, view, delete logs or to generate new tokens</li>
            <li>insert: token only valid for inserting rows</li>
            <li>view: URL for viewing the log</li>
          </ul>
          <pre class="doc">
{
    "status": "created",
    "admin": "XEpzD4p6zyJW795WpKsthUXurCsQ",
    "insert": "ncSZX8Qz8Kqzj9Kbk4F6HR22xAF3",
    "view": "https://<?=$hostname?>/api/log/1KxAkipMQDsShjzua8xm1ncdNfsY3"
}</pre>
        </div>
      </div>

      <div class="columns">
        <div class="column is-2">
          <span class="tag is-info is-light">
            name
          </span>
        </div>
        <div class="column">
          A custom name for the log, this name is displayed when viewing the log.
        </div>
      </div>
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-info is-light">
            retention
          </span>
        </div>
        <div class="column">
          Determines when rows are deleted from a log, e.g. a retention of 15 means rows older than 15 days are automatically deleted.<br>
          Provide a number between 1 and <?=(new Settings)->get()->getMaxRetention()?><br>
          Default value: <?=$defaultAccount->getMaxRetention()?>
        </div>
      </div>
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-info is-light">
            username
          </span>
        </div>
        <div class="column">
          Optional username, when setting a username, <a href="/login">login</a> with this username and password instead of remembering the URLs.
        </div>
      </div>
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-info is-light">
            password
          </span>
        </div>
        <div class="column">
          Optional password (max. 70 characters), required when setting a username.
        </div>
      </div>
    </div>
    <div class="block">
      The following example creates a new log
    </div>
    <pre class="doc">
curl -X POST <?=$url?>/api/log</pre>
    <div class="block"><br>
      The next example creates a new log with name <em>firstlog</em> and a retention of <em>7</em> days, e.g. all logs older than 7 days are automatically removed. Also sets a username <em>user1</em> and a password <em>hunter2</em>
    </div>
    <pre class="doc">
curl -d 'name=firstlog&amp;retention=7&amp;username=test1&amp;password=hunter2' <?=$url?>/api/log</pre>
  </section>

  <section class="section">
    <div class="title">
      Update log
    </div>

    <div class="block">
      Sent a PATCH request to update an existing log.
    </div>
    <div class="block">
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-success is-light">
            Endpoint
          </span>
        </div>
        <div class="column">
          /api/log
        </div>
      </div>
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-success is-light">
            Method
          </span>
        </div>
        <div class="column">
          PATCH
        </div>
      </div>
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-success is-light">
            Return value
          </span>
        </div>
        <div class="column">
          HTTP status code 200 (OK).<br>
          JSON object with the result.
          <pre class="doc">
{
    "status": "updated",
    "usermsg": "Password successfully updated"
}</pre>
         </div>
      </div>

      <div class="columns">
        <div class="column is-2">
          <span class="tag is-warning is-light">
            Header: authorization
          </span>
        </div>
        <div class="column">
          Required, use an admin token.
        </div>
      </div>
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-info is-light">
            name
          </span>
        </div>
        <div class="column">
          A custom name for the log, this name is displayed when viewing the log.<br>
          Provide an empty string "" to clear the name.
        </div>
      </div>
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-info is-light">
            username
          </span>
        </div>
        <div class="column">
          Username, can only be set when the log has no username yet, cannot be changed.
        </div>
      </div>
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-info is-light">
            password
          </span>
        </div>
        <div class="column">
          New password, used for login.
        </div>
      </div>
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-info is-light">
            retention
          </span>
        </div>
        <div class="column">
          Update the log retention. Note: the updated retention is set for new rows, existing rows will not be changed.
        </div>
      </div>
    </div>
    <div class="block">
      The following example clears the name of the log and updates the password.
    </div>
    <pre class="doc">
curl -H "Authorization: XEpzD4p6zyJW795WpKsthUXurCsQ" \
-d 'name=""&amp;username=test2&amp;password=hunter3' \
-X PATCH \
<?=$url?>/api/log</pre>
  </section>

  <section class="section">
    <div class="title">
      Delete log
    </div>

    <div class="block">
      Sent a DELETE request to delete an existing log.<br>
    </div>
    <div class="block">
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-success is-light">
            Endpoint
          </span>
        </div>
        <div class="column">
          /api/log
        </div>
      </div>
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-success is-light">
            Method
          </span>
        </div>
        <div class="column">
          DELETE
        </div>
      </div>
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-success is-light">
            Return value
          </span>
        </div>
        <div class="column">
          HTTP status code 200 (OK).<br>
          Status message indicating deleted
          <pre class="doc">
{
  "status": "deleted"
}</pre>
        </div>
      </div>
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-warning is-light">
            Header: authorization
          </span>
        </div>
        <div class="column">
          Required, use an admin token
        </div>
      </div>
    </div>
    <div class="block">
      Delete a log by sending a DELETE request.
    </div>
    <pre class="doc">
curl -H "Authorization: XEpzD4p6zyJW795WpKsthUXurCsQ" \
-X DELETE \
<?=$url?>/api/log</pre>
  </section>

  <section class="section">
    <div class="title">
      Get logs
    </div>

    <div class="block">
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-success is-light">
            Endpoint
          </span>
        </div>
        <div class="column">
          /api/log
        </div>
      </div>
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-success is-light">
            Method
          </span>
        </div>
        <div class="column">
          GET
        </div>
      </div>
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-success is-light">
            Return value
          </span>
        </div>
        <div class="column">
          HTTP status code 200 (OK).<br>
          The result contains both log metadata and log rows.<br>
        </div>
      </div>

      <div class="block">
        Without parameters, max. <?=$defaultAccount->getDashboardRows()?> rows are returned in the output.<br>
      </div>
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-warning is-light">
            Header: authorization
          </span>
        </div>
        <div class="column">
          Use a view or admin token to view the log.<br>
          The authorization code can also be set in the URL, e.g. <?=$url?>/api/log/1KxAkipMQDsShjzua8xm1ncdNfsY3</pre>
        </div>
      </div>
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-info is-light">
            limit
          </span>
        </div>
        <div class="column">
          Return this many rows, can be any number between 1 and <?=Constants::getMaxRows() ?? 1000?>.
        </div>
      </div>
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-info is-light">
            date
          </span>
        </div>
        <div class="column">
          Search logs on or before the given date. Provide the date as yyyy-mm-dd with an optional time.
          Some valid search dates:<br>
          2024-02-10<br>
          2024-02-10 14:41:19<br>
          2024-02-10 14:41:19.307<br>
        </div>
      </div>
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-info is-light">
            data
          </span>
        </div>
        <div class="column">
          Case insensitive text search, for searching any text in the row, like an IP address, UUID, unique value, etc.<br>
          The search parameter does not support partial matches or wildcards.<br>
          Searching is done using a bloom filter, if you get the error message "Search timeout, showing partial results." try a filter with less common search terms.
        </div>
      </div>
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-info is-light">
            type
          </span>
        </div>
        <div class="column">
          Set to <em>html</em> or <em>json</em> to force the output format
        </div>
      </div>
    </div>
    <div class="block">
      The return value consists of log metadata and a list of rows.<br><br>
      <div class="columns mb-0">
        <div class="column is-2">
          <span class="tag">
            base_url
          </span>
        </div>
        <div class="column">
          canonical url for the log
        </div>
      </div>
      <div class="columns mb-0">
        <div class="column is-2">
          <span class="tag">
            url
          </span>
        </div>
        <div class="column">
          log url including search parameters
        </div>
      </div>
      <div class="columns mb-0">
        <div class="column is-2">
          <span class="tag">
            prev
          </span>
        </div>
        <div class="column">
          link to the page with newer results
        </div>
      </div>
      <div class="columns mb-0">
        <div class="column is-2">
          <span class="tag">
            next
          </span>
        </div>
        <div class="column">
          link to the page with older results
        </div>
      </div>
      <div class="columns mb-0">
        <div class="column is-2">
          <span class="tag">
            fixed_search
          </span>
        </div>
        <div class="column">
          fixed search string for public dashboards
        </div>
      </div>
      <div class="columns mb-0">
        <div class="column is-2">
          <span class="tag">
            create
          </span>
        </div>
        <div class="column">
          UTC log creation date and time
        </div>
      </div>
      <div class="columns mb-0">
        <div class="column is-2">
          <span class="tag">
            modify
          </span>
        </div>
        <div class="column">
          UTC log date and time of the last metadata mutation
        </div>
      </div>
      <div class="columns mb-0">
        <div class="column is-2">
          <span class="tag">
            name
          </span>
        </div>
        <div class="column">
          custom name provided when creating or patching the log
        </div>
      </div>
      <div class="columns mb-0">
        <div class="column is-2">
          <span class="tag">
            authorization
          </span>
        </div>
        <div class="column">
          yes if the log is password protected
        </div>
      </div>
      <div class="columns mb-0">
        <div class="column is-2">
          <span class="tag">
            retention
          </span>
        </div>
        <div class="column">
          number of days log rows are kept
        </div>
      </div>
      <div class="columns mb-0">
        <div class="column is-2">
          <span class="tag">
            total_rows
          </span>
        </div>
        <div class="column">
          number of rows in this log (cached)
        </div>
      </div>
      <div class="columns mb-0">
        <div class="column is-2">
          <span class="tag">
            rows
          </span>
        </div>
        <div class="column">
          element containing the log rows
        </div>
      </div>
    </div>
    <div class="block">
      Get 2 rows from a log.
    </div>
    <pre class="doc">
curl <?=$url?>/api/log/1KxAkipMQDsShjzua8xm1ncdNfsY3?limit=2</pre>
  </section>

  <section class="section">
    <div class="title">
      Login
    </div>

    <div class="block">
      Login with a username and password. A username can be set when creating or updating a log.
    </div>
    <div class="block">
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-success is-light">
            Endpoint
          </span>
        </div>
        <div class="column">
          /api/login
        </div>
      </div>
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-success is-light">
            Method
          </span>
        </div>
        <div class="column">
          POST
        </div>
      </div>
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-success is-light">
            Return value
          </span>
        </div>
        <div class="column content">
          HTTP status code 200 (Ok).<br>
          JSON object containing several tokens and an URL to view the log, see creating a new log for the token types.
          <pre class="doc">
{
    "account": "Anonymous",
    "username": "test1",
    "userhash": "1b4f0e9851971998",
    "admin": "XEpzD4p6zyJW795WpKsthUXurCsQ",
    "insert": "ncSZX8Qz8Kqzj9Kbk4F6HR22xAF3",
    "view": "https://<?=$hostname?>/api/log/1KxAkipMQDsShjzua8xm1ncdNfsY3"
}</pre>
        </div>
      </div>

      <div class="block">
        The following POST parameters are required.
      </div>
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-info is-light">
            username
          </span>
        </div>
        <div class="column">
          Username, set when creating or updating the log. The username can be set at any time but it cannot be changed.
        </div>
      </div>
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-info is-light">
            password
          </span>
        </div>
        <div class="column">
          Password, set when creating or updating the log.
        </div>
      </div>
    </div>
    <div class="block">
      Login from the command line, returning an admin, insert and view token.
    </div>
    <pre class="doc">
curl -d 'username=test1&amp;password=hunter2' <?=$url?>/api/login</pre>
  </section>

  <section class="section">
    <div class="title">
      List tokens
    </div>

    <div class="block">
      List all tokens, max <?=Token::getMaxTokens()?> different tokens per log can be created.<br>
      Tokens are case sensitive. Tokens are cached for up to 30 seconds, i.e. after adding or deleting a token, it can take a few seconds to become visible.<br>
      Tokens are required to view, update or delete logs and required to insert new log rows.
    </div>
    <div class="block">
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-success is-light">
            Endpoint
          </span>
        </div>
        <div class="column">
          /api/token
        </div>
      </div>
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-success is-light">
            Method
          </span>
        </div>
        <div class="column">
          GET
        </div>
      </div>
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-success is-light">
            Return value
          </span>
        </div>
        <div class="column content">
          HTTP status code 200 (Ok).<br>
          JSON object containing all tokes, see creating a new log for the token types.<br>
          The search parameters shows the required querystring parameters for public dashboards.
          <pre class="doc">
[
    {
        "privilege": "admin",
        "token": "XEpzD4p6zyJW795WpKsthUXurCsQ"
    },
    {
        "privilege": "insert",
        "token": "ncSZX8Qz8Kqzj9Kbk4F6HR22xAF3"
    },
    {
        "privilege": "view",
        "token": "1KxAkipMQDsShjzua8xm1ncdNfsY3",
        "search": ""
    }
}</pre>
        </div>
      </div>

      <div class="columns">
        <div class="column is-2">
          <span class="tag is-warning is-light">
            Header: authorization
          </span>
        </div>
        <div class="column">
          Required, use an admin token
        </div>
      </div>
    </div>
    <div class="block">
      Show all tokens belonging to a log.
    </div>
    <pre class="doc">
curl -H "Authorization: XEpzD4p6zyJW795WpKsthUXurCsQ" \
https://<?=$hostname?>/api/token</pre>
  </section>

  <section class="section">
    <div class="title">
      Create new token
    </div>

    <div class="block">
      Create a new token, max <?=Token::getMaxTokens()?> different tokens per log can be created.
    </div>
    <div class="block">
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-success is-light">
            Endpoint
          </span>
        </div>
        <div class="column">
          /api/token
        </div>
      </div>
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-success is-light">
            Method
          </span>
        </div>
        <div class="column">
          POST
        </div>
      </div>
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-success is-light">
            Return value
          </span>
        </div>
        <div class="column content">
          HTTP status code 201 (Created).<br>
          JSON containing a new token.
          <pre class="doc">
{
    "token": "XEpzD4p6zyJW795WpKsthUXurCsQ"
}</pre>
        </div>
      </div>

      <div class="columns">
        <div class="column is-2">
          <span class="tag is-info is-light">
            scope
          </span>
        </div>
        <div class="column">
          Required, must be one of these: admin, insert or view.<br>
          Admin tokens grant unrestricted access to a log. Insert tokens can only be used for inserting rows, view tokens can only be used for viewing logs.
        </div>
      </div>
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-warning is-light">
            Header: authorization
          </span>
        </div>
        <div class="column">
          Required, use an admin token
        </div>
      </div>
    </div>
    <div class="block">
      Create a new admin token.
    </div>
    <pre class="doc">
curl -H "Authorization: XEpzD4p6zyJW795WpKsthUXurCsQ" \
https://<?=$hostname?>/api/token \
-d 'scope=admin'</pre>
    <div class="block">
      Create a new view token with a fixed selection (public dashboard), i.e. the search parameter is always at least "user1" and "firefox"
    </div>
    <pre class="doc">
curl -H "Authorization: XEpzD4p6zyJW795WpKsthUXurCsQ" \
https://<?=$hostname?>/api/token?data=user1+firefox \
-d 'scope=view'</pre>
  </section>

  <section class="section">
    <div class="title">
      Delete token
    </div>

    <div class="block">
      Tokens can be created and deleted at any time but at least one admin token must remain.
    </div>
    <div class="block">
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-success is-light">
            Endpoint
          </span>
        </div>
        <div class="column">
          /api/token
        </div>
      </div>
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-success is-light">
            Method
          </span>
        </div>
        <div class="column">
          DELETE
        </div>
      </div>
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-success is-light">
            Return value
          </span>
        </div>
        <div class="column content">
          HTTP status code 200 (Ok).<br>
          JSON containing details.
          <pre class="doc">
{
    "status": "success",
    "detail": "Removed token SJA5fKSaXVwDRkWC7ZcB9B41CPGx"
}</pre>
        </div>
      </div>

      <div class="columns">
        <div class="column is-2">
          <span class="tag is-warning is-light">
            Header: authorization
          </span>
        </div>
        <div class="column">
          Required, use an admin token
        </div>
      </div>
      <div class="columns">
        <div class="column is-2">
          <span class="tag is-info is-light">
            token
          </span>
        </div>
        <div class="column">
          Provide the token to delete in POST parameter.
        </div>
      </div>
    </div>
    <div class="block">
      Delete token SJA5fKSaXVwDRkWC7ZcB9B41CPGx
    </div>
    <pre class="doc">
curl -H "Authorization: XEpzD4p6zyJW795WpKsthUXurCsQ" \
https://<?=$hostname?>/api/token \
-X DELETE \
-d 'token=SJA5fKSaXVwDRkWC7ZcB9B41CPGx'</pre>
  </section>

  <section class="section">
    <div class="title">
      Errors
    </div>

    <div class="block">
      API calls can fail with any of the errors listed here.
    </div>
    <div class="block">
      <div class="columns">
        <div class="column is-3">
          <span class="tag is-danger is-light">
            ERROR_FORBIDDEN
          </span>
        </div>
        <div class="column">
          403 &dash; authorization is not provided or invalid
        </div>
      </div>
      <div class="columns">
        <div class="column is-3">
          <span class="tag is-danger is-light">
            ERROR_INVALID_ACTION
          </span>
        </div>
        <div class="column">
          400 &dash; client requested an unknown API endpoint
        </div>
      </div>
      <div class="columns">
        <div class="column is-3">
          <span class="tag is-danger is-light">
            ERROR_INVALID_JSON
          </span>
        </div>
        <div class="column">
          400 &dash; the action requires JSON but the input is not valid JSON
        </div>
      </div>
      <div class="columns">
        <div class="column is-3">
          <span class="tag is-danger is-light">
            ERROR_INVALID_USERNAME
          </span>
        </div>
        <div class="column">
          400 &dash; a username was provided but it contains invalid characters
        </div>
      </div>
      <div class="columns">
        <div class="column is-3">
          <span class="tag is-danger is-light">
            ERROR_METHOD_NOT_ALLOWED
          </span>
        </div>
        <div class="column">
          405 &dash; the API support GET, POST, PATCH and DELETE but something else was provided
        </div>
      </div>
      <div class="columns">
        <div class="column is-3">
          <span class="tag is-danger is-light">
            ERROR_NO_SERVER_AVAILABLE
          </span>
        </div>
        <div class="column">
          500 &dash; the backend cannot find a server to process the incoming request, try again later
        </div>
      </div>
      <div class="columns">
        <div class="column is-3">
          <span class="tag is-danger is-light">
            ERROR_NO_VALID_ROWS
          </span>
        </div>
        <div class="column">
          400 &dash; none of the provided row(s) are valid when trying to insert new rows
        </div>
      </div>
      <div class="columns">
        <div class="column is-3">
          <span class="tag is-danger is-light">
            ERROR_NOT_FOUND
          </span>
        </div>
        <div class="column">
          404 &dash; the requested log is not found
        </div>
      </div>
      <div class="columns">
        <div class="column is-3">
          <span class="tag is-danger is-light">
            ERROR_PROVIDE_SCOPE_ADMIN_INSERT_VIEW
          </span>
        </div>
        <div class="column">
          400 &dash; generating new tokens requires a scope, consult the documentation
        </div>
      </div>
      <div class="columns">
        <div class="column is-3">
          <span class="tag is-danger is-light">
            ERROR_SERVICE_UNAVAILABLE
          </span>
        </div>
        <div class="column">
          503 &dash; connection to the backend database failed, try again later
        </div>
      </div>
      <div class="columns">
        <div class="column is-3">
          <span class="tag is-danger is-light">
            ERROR_TOO_MANY_REQUESTS
          </span>
        </div>
        <div class="column">
          429 &dash; too many requests, try again later
        </div>
      </div>
      <div class="columns">
        <div class="column is-3">
          <span class="tag is-danger is-light">
            ERROR_TOKEN_LIMIT_REACHED
          </span>
        </div>
        <div class="column">
          400 &dash; too many tokens generated
        </div>
      </div>
      <div class="columns">
        <div class="column is-3">
          <span class="tag is-danger is-light">
            ERROR_UNKNOWN
          </span>
        </div>
        <div class="column">
          500 &dash; something terrible happened but we don't know what, try again later
        </div>
      </div>
      <div class="columns">
        <div class="column is-3">
          <span class="tag is-danger is-light">
            ERROR_USERNAME_ALREADY_EXISTS
          </span>
        </div>
        <div class="column">
          400 &dash; creating a new log failed because the username is already taken
        </div>
      </div>
    </div>
  </section>
</div>
