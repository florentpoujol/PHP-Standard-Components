## Standard Components

This project aims to provide standard components that you find in many PHP libraries and Frameworks but as a PHP extension.

The goal being mostly to reduce userland code and speed things up a little.

Such components include:
- routing
- cache (driver for files, redis and memcache)
- logger
- dependency container/injection
- HTTP wrapper (Request/Response classes)
- events
- filesystem
- Console ?
- Yaml parsing
- ACL
- Validator

The implementation of each is heavily inspired by what is alredy done in the symphony/iluminate components or other very popular libraries and respect the PSR recomendations -where applicable-.

However the goal is not to port the PHP code of these components as an extension. The extension is to provide a basic, common implementation that would be suitable in most case. 
It is the responsibility to each framework/users to decorate the extension's classes in the userland with their respective linking and additionnal features.

They aims to be a basic building block at the language level.

Ultimately, it can be imagined that these classes are integrated as a default component of PHP, probably part of the SPL extension...
