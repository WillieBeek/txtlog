class Txtlog {
  apiurl = '/api/log';
  inurl = $('#logdomain').val() + '/api/log';
  loginurl = '/api/login';
  tokenurl = '/api/token/';

  constructor() {
    this.parseCookie();
    this.setUrl(this.logurls);
  }

  // https://stackoverflow.com/questions/4810841/how-can-i-pretty-print-json-using-javascript
  syntaxHighlight(str) {
    try {
      JSON.parse(str);
    } catch(e) {
      return str;
    }

    str = str.replace(/    /g, '  ');
    str = str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    str = str.replace(/\\n/g, "\n");
    return str.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
      var cls = 'number';
      if(/^"/.test(match)) {
        if(/:$/.test(match)) {
          cls = 'key';
        } else {
          cls = 'string';
        }
      } else if(/true|false/.test(match)) {
        cls = 'boolean';
      } else if (/null/.test(match)) {
        cls = 'null';
      }

      return '<span class="json_' + cls + '">' + match + '</span>';
    });
  }

  parseCookie() {
    try {
      this.logurls = JSON.parse(document.cookie.split(";").find((row) => row.startsWith("logurls="))?.split("=")[1]);
    } catch(e) {
    }
  }

  setCookie(data) {
    let month = 60*60*24*30;
    document.cookie = 'logurls=' + JSON.stringify(data) + ';max-age=' + month + ';SameSite=Strict;Secure;Path=/';
    this.logurls = data;
    this.setUrl(data);
  }

  logout() {
    document.cookie = 'logurls=;expires=0;SameSite=Strict;Secure;Path=/';
    this.logurls = undefined;
    $('#logoutmsg,#loginform').show();
    $('.logininfo').hide();
  }

  generateNewLog() {
    this.logout();
    let that = this;

    $.post(this.apiurl, function(data) {
      that.logurls = data;
      that.setCookie(data)
    });
  }

  setUrl(data) {
    if(data === undefined) {
      return;
    }
    let url = data.view || this.apiurl;
    let insert = data.insert === undefined ? '' : data.insert.split('/').pop();
    let viewcode = url === undefined ? '' : url.split('/').pop();

    $('.logid').attr('href', url);
    $('.logurl').text(url);
    $('#linuxapp').attr('href', '/txtlog?type=ssh&auth=' + insert);
    $('#rdpapp').attr('href', '/txtlog?type=rdp&auth=' + insert);
    $('.requireslog').prop('disabled', false);

    $('.logcode').val(viewcode);
    this.setUpgradeUrl(data);
    this.parseCookie();
  }

  setUpgradeUrl(data) {
    if(data.username === undefined) {
      $('.upgradebutton').text('Set a username first');
      return;
    }

    $('.username').val(data.username);
    $('.upgradeurl1').attr('href', $('#upgradebase1').val() + '?client_reference_id=' + data.userhash);
    $('.upgradeurl2').attr('href', $('#upgradebase2').val() + '?client_reference_id=' + data.userhash);
    $('.upgradeurl1 button,.upgradeurl2 button').prop('disabled', false);
  }

  setLoggedInText() {
    $('.logininfo').show();
    $('#loggedinuser').text(this.logurls.username);
    $('#loggedinaccount').text(this.logurls.account);
    $('#loggedinretention').text(this.logurls.retention);
  }

  login() {
    let username = $('#username').val();
    let password = $('#password').val();
    let that = this;
    this.logout();

    $.post(this.loginurl, { username: username, password: password }, function(data) {
      that.setCookie(data);
      that.setLoggedInText();
      $('#loginform,#logoutmsg').hide();
      // Show remove public view URL button
      if(data.view !== undefined) {
        $('.accessdenied').hide();
        $('#removeview').show();
      }
    }).fail(function(xhr, status, error) {
      $('.accessdenied').show();
    });
  }

  addLog() {
    let logdata = $('#logdata').val();
    let token = this.logurls.insert;
    let that = this;

    $.ajax({
      url: this.inurl,
      type: 'POST',
      data: logdata,
      headers: { 'Authorization': token },
      processData: false,
      success: function(result) {
        // Parse JSON for readability
        let str = JSON.stringify(result, null, 4);
        str = that.syntaxHighlight(str);

        $('#addlog-result').show().html(str);
        $('#addlog-curl').show().html('curl ' + that.inurl + ' \\<br>-H "Authorization: ' + token + '" \\<br>-d \'' + logdata.trim() + "'");
      },
      error: function(xhr, status, error) {
       $('#addlog-result').show().html(xhr.status + ': ' + xhr.statusText);
      }
    });
  }

  protect() {
    let username = $('#username').val();
    let password = $('#password').val();
    let that = this;

    if(username.length < 1 || password.length < 1) {
      return;
    }
    if(this.logurls.username !== undefined && this.logurls.username !== username) {
      $('#protectresult').text('Invalid username');
      return;
    }

    $.ajax({
      url: this.apiurl,
      type: 'PATCH',
      data: { username: username, password: password },
      headers: { 'Authorization': this.logurls.admin },
      success: function(result) {
        $('#protectresult').text(result.usermsg);
        that.login();
      },
      error: function(result) {
        $('#protectresult').text(result.responseJSON.error);
      }
    });
  }

  removeViewToken() {
    let viewtoken = this.logurls.view.split('/').pop();
    let that = this;

    $.ajax({
      url: this.tokenurl,
      type: 'DELETE',
      data: { token: viewtoken },
      headers: { 'Authorization': this.logurls.admin },
      success: function(result) {
        $('#removeviewresult').text(result.detail);
        // Update cookie
        that.login();
      },
      error: function(result) {
        $('#removeviewresult').text(result.responseJSON.error);
      }
    });
  }
}

$(document).ready(function() {
  const txtlog = new Txtlog();

  // Set light or dark mode
  $('html').attr('data-theme', localStorage.getItem('theme'));

  if(txtlog.logurls === undefined) {
    txtlog.generateNewLog();
  }

  $(document).on('click', '#generatenew', function() {
    txtlog.generateNewLog();
    $('#generatenewinfo').show();
  });
  
  // Add log row
  $(document).on('click', '#addlog', function() {
    txtlog.addLog();
  });

  // Add color to the dashboard
  $('.logline').each(function() {
    str = txtlog.syntaxHighlight($(this).text());

    // Flatten contents by removing starting and ending { } tags
    str = str.replace(/^{\n/, '').replace(/\n}$/, '');

    // Reduce indentation
    str = str.replace(/^  /gm, '');

    $(this).html(str);
  });
  $('.dashboard,.footer').show();

  // Documentation tabs
  $(document).on('click', '.examples ul li', function() {
    let lang = $(this).data('example');
    $('.example').hide();
    $('.examples ul li').removeClass('is-active');
    $('.' + lang + '-example').show();
    $('.tab' + lang).addClass('is-active');
  });

  // Default to cURL documentation
  $('.curl-example').show();

  // Protect a log
  $(document).on('keyup', '#username,#password', function(e) {
    if(e.which == 13) {
      $('#protect').trigger('click');
    }
  });
  $(document).on('click', '#protect', function() {
    txtlog.protect();
  });
  $(document).on('click', '#removeviewbutton', function() {
    txtlog.removeViewToken();
  });

  // Login
  if(txtlog.logurls !== undefined && txtlog.logurls.username !== undefined && txtlog.logurls.username.length > 0) {
    txtlog.setLoggedInText();
  } else {
    $('#loginform').show();
  }

  $('#login').submit(function(e) {
    e.preventDefault();
    txtlog.login();
  });

  // Logout
  $(document).on('click', '#logout', function() {
    txtlog.logout();
  });

  // Toggle between light and dark mode
  $(document).on('click', '#dark', function() {
    let newTheme = $('html').attr('data-theme') == 'dark' ? 'light' : 'dark';
    $('html').attr('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
  });
});
