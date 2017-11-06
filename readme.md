# PHP Standard Components

This project aims to research and design basic standard components, that is a library that you find in practice in all major frameworks.

Such components typically include:
- [logger](doc/logger.md)
- [cache](doc/cache.md)
- event emitter
- dependency container
- routing
- HTTP wrapper (Request/Response classes)
- ??

The idea is to research how the major frameworks (or the libraries they depends on) handle these subjects, and implements a reasonable common ground that could be implemented as its own standalone library.

It's not the design goal of a standard component to implement all of the features found in all the framework, but to find thee most common features, while thinking about how these framework would rewrite their code to be based on that standard component.

The [FIG's PSRs](http://www.php-fig.org/psr/) are also to bee taken into consideration.

A good example of such standard component is the logger:
- both Laravel and Symfony uses [Monolog](https://github.com/Seldaek/monolog)
- The Zend framework do not use Monolog but its logger API is VERY similar (same system of logger, processors, writers (handlers in Monolog), filter and formatters)
