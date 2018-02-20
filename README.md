# Arcanist Detekt Linter

This is a [Detekt](https://github.com/arturbosch/detekt) Linter plugin for Arcanist.
Detekt is a static code analysis tool for Kotlin code.

## Installation

### Prerequisites

In short, you will need to following:
- Java (to run the jar)
- The Detekt Jar. See [this](https://github.com/arturbosch/detekt#build--use-the-commandline-interface).
- The Detekt.yml file to configure Detekt: ([Sample Detekt.yml](https://raw.githubusercontent.com/arturbosch/detekt/master/detekt-cli/src/main/resources/default-detekt-config.yml)).

### Project-specific installation

You can add this repository as a git submodule to your project.
Include the following in your `.arcconfig` file to load this module:

```json
{
  "load": ["path/to/arc-detekt-linter"]
}
```

### Global installation
`arcanist` is also able to load modules from an absolute path. But there is one
more trick - it will also search for modules in a directory up one level
from itself.

It means that you can clone this repository to the same directory
where `arcanist` and `libphutil` are located. In the end it should
look like this:

```sh
> ls
arcanist
arc-detekt-linter
libphutil
```

In this case your `.arcconfig` should look like

```json
{
  "load": ["arc-detekt-linter"]
  // ...
}
```

## Setup
Once installed the linter can be used and configured just as any other arcanist linter.

Configuration `.arclint`:

```json
{
    "linters": {
        "detekt": {
            "type": "detekt",
            "jar": "path/to/detekt-cli-1.0.0.[version].jar",
            "detektConfig": "path/to/detekt.yml",
             "include": "(.kt$)"
        },
        // ...
    }
}
```
You can test your settings by running `arc lint`, arcanist will tell when something goes wrong.
## Arc Lint Feature Support
The linter will:
- Set the linenumber on which an lint error is detected.
- Show what the linting error was
- Set the severity
- Give an explanation of what went wrong

It will not:
- Autocorrect errors, although sometimes it gives suggestions.

## Usage
Just run `arc lint` to lint the kotlin files and show warnings on the changed lines OR run `arc lint --lintall` to also show warning occuring in other lines of the changed files.

## License

Licensed under the Apache 2.0 license. See LICENSE for details.
