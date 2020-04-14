# Polyel
A PHP framework for building beautiful, expressive and fast web applications

**Status: In Development; unstable** 🛠

[![PHP Version](https://img.shields.io/badge/PHP-%3E=7.3-brightgreen.svg?maxAge=2592000)](https://secure.php.net/)
[![Swoole Version](https://img.shields.io/badge/swoole-%3E=4.2.1-brightgreen.svg?maxAge=2592000)](https://github.com/swoole/swoole-src)

# What is Polyel?
Polyel is a PHP framework based on the Swoole networking framework which is a C/C++ extension written for PHP and runs as a PHP CLI application, allowing PHP to be used and follow the same sort of principle as Node.js and following the MVC architectural pattern.

Polyel brings everything together and provides features to make web development quicker and more secure. Some features include built-in support for authentication and authorization, XSS filtering, CSRF protection, database wrapper support, email sending, input validation, time manipulation, built-in templating system, front end view support, message management and much more...

Allowing web applications to be built using PHP that is highly scalable and implements support for WebSockets, concurrent connections, Task workers, Async-MySQL and non-blocking I/O programming.

# Planned Features & Roadmap
The planned development feature list for the Polyel framework:
- Fast, easy to use Routing Engine 🗹
- An async database query builder ☐
- Custom built DIC (Container) where everything is preloaded (even controllers), speeding up requests 🗹
- A complete HTTP server with built-in support for Ajax & web sockets ☐
- Easy to use session management system ☐
- OOP MVC framework model built around a async paradigm 🗹
- Quick, elegant and simple built in templating engine ☐
- Simple configuration management right from the start 🗹
- Built in Coroutine support based on Swoole PHP 🗹
- Redis client which supports different use cases ☐
- Powerful and flexible built-in logger ☐
- Middleware system 🗹
- Built-in feature rich modules such as: Time processing, message system, flash messaging, markdown parser, email sending, data validation, pagination etc. ☐
- File storage service (Planned support for FTP and cloud storage) 🗹
- Automatic XSS filtering & CSRF protection ☐
- Automatic SSL assigning using Let's Encrypt ☐
- Element templates; handles rendering dynamic page components with logic and data ☐

# Licence

Polyel uses the [Apache License Version 2.0](http://www.apache.org/licenses/LICENSE-2.0.html)
