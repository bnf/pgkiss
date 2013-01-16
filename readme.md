[comment]: http:// "pgkiss is a very simple PHP wrapper for "git http-backend"-CGI script. It enables hosting of Git repositories on a web server with just PHP and Git available. Currently relies on web-server provided authentication if (or when) such is needed."

# PHP wrapper for "git-http-backend"-CGI script.

Make _git-http-backend_ work when CGI execution isn't available.

Host Git repositories on a web server with just PHP and Git.

### USAGE
1. Drop `git.php` on a web server with PHP support
2. Edit `git.php`: set `REPODIR` (and, if necessary, `GITPATH`)
3. Create git repositories under `REPODIR` directory:

        $ mkdir newrepo; cd newrepo
        $ git --init --bare
        $ touch git-daemon-export-ok        # git-http-backend security check
        $ git config http.receivepack true  # enable pushing to this repository

4. Ensure PHP has read & write access to the repositories.

_The repositories can now be used._

URL will be something like `http(s)://domain.tld/something/git.php/<repository>`  
 

---

### NOTE
* You can remove "git.php" from the url by using [url rewriting][gurlrewrite].
* You can configure web server to [require a password][greqpass] when accessing the repositories.
* Error output from `git-http-backend` will be written to `err.log` (permissions..)

[gurlrewrite]:	https://www.google.fi/search?q=url+rewriting
[greqpass]:		https://www.google.fi/search?q=web+server+password+protect

Thanks to people for [sharing][tnx1] [information][tnx2].

[tnx1]: http://www.fun2code.de/articles/wrapping_perl_with_php/wrapping_perl_with_php.html
[tnx2]: https://github.com/frankusrs/PHP-CGI-Wrapper/blob/master/cgi_wrapper.php

ps. keep it simple, stupid (if this counts ;)
