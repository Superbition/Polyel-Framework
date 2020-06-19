# Polyel
A PHP framework for building beautiful, expressive and fast web applications, based on the Swoole networking library

**Status: In Development; unstable** 🛠

[![PHP Version](https://img.shields.io/badge/PHP-%3E=7.3-brightgreen.svg?maxAge=2592000)](https://secure.php.net/)
[![Swoole Version](https://img.shields.io/badge/swoole-%3E=4.2.1-brightgreen.svg?maxAge=2592000)](https://github.com/swoole/swoole-src)

# What can be done with Polyel?
Polyel is an MVC (Model-View-Controller) PHP framework based on the Swoole networking library, which is a C++ extension written for PHP and runs as a CLI application, allowing you to build high-performance web applications using both synchronous and asynchronous programming.

A framework that brings everything together, providing features to make web development quicker and more secure. Some features include built-in support for authentication and authorization, XSS filtering, CSRF protection, database query builder support, async email sending, input validation, time manipulation, built-in templating & view service, message management and much more...

Allowing you to create highly scalable applications and with support for web sockets, concurrent connections, Task workers, connection pools, async MySQL and non-blocking I/O programming.

# Planned Features & Roadmap
The planned development feature list for the Polyel framework:
- [x] Fast, easy to use Routing Engine with built in automatic caching
- [x] An async database query builder
- [x] Custom built DIC (Container) where everything is preloaded (even controllers), speeding up requests
- [ ] A complete HTTP server with built-in support for Ajax & web sockets
- [x] Easy to use session management system
- [x] OOP MVC framework model built around a async paradigm
- [x] Quick, elegant and simple built in templating engine
- [x] Simple configuration management right from the start
- [x] Built in Coroutine support based on Swoole PHP
- [ ] Redis client which supports different use cases
- [ ] Powerful and flexible built-in logger
- [x] Middleware system
- [ ] Built-in feature rich modules such as: Time processing, message system, flash messaging, markdown parser, email sending, data validation, pagination etc.
- [x] File storage service (Planned support for FTP and cloud storage)
- [ ] Automatic XSS filtering & CSRF protection
- [ ] Automatic SSL assigning using Let's Encrypt
- [x] Element templates; handles rendering dynamic page components with logic and data

# Community

Join the [PHPNexus.io](https://PHPNexus.io) community and come talk about Polyel, PHP or Swoole.

# Licence

Polyel uses the [Apache License Version 2.0](http://www.apache.org/licenses/LICENSE-2.0.html)
