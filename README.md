ZeroMass-Doc
============

Documentation generator for ZeroMass based projects

ZeroMass-Doc is a web application for vieweing the documentation extracted from the source code of ZeroMass based projects. In order to use it, you must install the app on your development webserver, where it has filesystem access to the projects being documented.

Installation
------------

Installation is the same as for any ZeroMass application. Grab the full repository and clone it into a directory on your disk:

    cd /srv
    sudo mkdir ZeroMass-Doc
    sudo chown $(whoami) ZeroMass-Doc
    cd ZeroMass-Doc
    git clone https://github.com/sergiosgc/ZeroMass-Doc.git .

Now, you need a host name for your ZeroMass-Doc installation. Edit /etc/hosts and add the line:

    127.0.0.1    zmdoc.dev

And finally, you need to configure your webserver. The webserver should serve any file that exists on the public directory of the app, it should interpret PHP files, and whenever a file does not exist, it should serve public/zeromass/com.sergiosgc.zeromass.php. For nginx + php-fpm, the configuration is like this:

    server {
      server_name zmdoc.dev;
     
      root /srv/ZeroMass-Doc/public;
      index index.php;
     
      location / {
        try_files $uri /zeromass/com.sergiosgc.zeromass.php?$args;
      }
     
      location ~ \.php$ {
        try_files $uri =404;
        include fastcgi_params;
        fastcgi_pass 127.0.0.1:9000;
      }
    }

If you are on Ubuntu, Debian or other Debian based distros, you can install nginx and php-fpm with:

    sudo apt-get install nginx php-fpm

and then add the configuration above to a file in /etc/nginx/sites-enabled.

IF you use other webservers, or other distros, the configuration shouldn't be much different. Try it out, then please fork this repository, add the instructions here and pull request the change. 

Usage
-----

Point your browser to http://zmdoc.dev/ You will be prompted for the directory of the ZeroMass project to document. ZeroMass-Doc is a ZeroMass project, so it can self-document. Try the directory ''/srv/ZeroMass-Doc'' and click Read the Docs. Then, drill down through the installed plugins and the plugins hook and PHPDoc documentation. 
