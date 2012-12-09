[comment]: http:// "pgkiss is a very simple PHP wrapper for "git http-backend"-CGI script. It enables hosting of Git repositories on a web server with just PHP and Git available. Currently relies on web-server provided authentication if (or when) such is needed."

## PHP wrapper for "git-http-backend"-CGI script.

Make **git-http-backend** work when **CGI** execution **isn't available**.

Host **Git repositories** on a **web server** with just **PHP** and **Git**.

### Usage
1. Drop git.php on a web server with PHP support.
2. Set *REPODIR* (and *GITPATH*, if necessary) in git.php
3. Create a Git repository under *REPODIR*:

        $ mkdir newrepo; cd newrepo
        $ git --init --bare
        $ touch git-daemon-export-ok        # git-http-backend security check
        $ git config http.receivepack true  # enable pushing to this repository

4. Ensure PHP has read & write access to the repository directory.

Now the repository can be used. Url is something like `http(s)://domain.tld/git.php/<repository>`

---

### Notes
You can drop "git.php" part from the url with *url rewriting*.

To protect repositories, you can configure web server to disallow access without password (see *todo* for regexp).

### Thanks to
People for [sharing][tnx1] [information][tnx2].

ps. keep it simple, stupid (if this counts ;)

[tnx1]: http://www.fun2code.de/articles/wrapping_perl_with_php/wrapping_perl_with_php.html
[tnx2]: https://github.com/frankusrs/PHP-CGI-Wrapper/blob/master/cgi_wrapper.php

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

but keep.it.simple!
