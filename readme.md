## PHP wrapper for "git http-backend"-CGI script.

"Make git http-backend work when CGI execution is not available."

### Usage
1. Drop git.php on a web server with PHP support.
2. Set "REPODIR" and "GITPATH" (in git.php)
3. Create GIT repository under REPODIR:

        $ mkdir newrepo; cd newrepo
        $ git --init --bare
        $ touch git-daemon-export-ok        # http-backend doesn't serve the repository unless this file is present
        $ git config http.receivepack true  # enable pushing to the repository

4. Ensure PHP has read & write access to the repository directory.

Now just use the repository as normal. URL is something like `http(s)://domain.tld/git.php/<repository>` unless you use URL rewriting, in which case you could drop git.php from the URL, for example.

### Note
To password-protect repositories, you can use ".htaccess", for example. You can separate read and write access that way too (see todo for regexp).
	
### "todo"
- force identity (probably easiest with hooks)
- format repository
   - http.getanyfile false // for access check to work with only upload- and receivepack checks
   - touch git-daemon-export-ok
   - git config http.receivepack true
   - (maybe) git config core.logallrefupdates true // ip logged by default (git http-backend doc)
- access check
   - "/git-upload-pack$", "/git-receive-pack$"
     (https://github.com/git/git/blob/e32a4581bcbf1cf43cd5069a0d19df07542d612a/http-backend.c#L532,
      http://www.kernel.org/pub/software/scm/git/docs/git-http-backend.html)

### Thanks to
 * http://www.fun2code.de/articles/wrapping_perl_with_php/wrapping_perl_with_php.html
 * https://github.com/frankusrs/PHP-CGI-Wrapper/blob/master/cgi_wrapper.php

PS. Keep It Simple, Stupid (if this counts ;)
