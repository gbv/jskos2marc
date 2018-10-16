# jskos2marc

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Build Status](https://travis-ci.com/gbv/jskos2marc.svg?branch=master)](https://travis-ci.com/gbv/jskos2marc)

> Convert JSKOS authority records to MARC21 

## Table of Contents

- [Background](#background)
- [Install](#install)
- [API](#api)
    - [jskos2marcxml](#jskos2marcxml)
    - [marcjson2marcxml](#marcjson2marcxml)
    - [jskos_decode](#jskos_decode)
- [License](#license)

## Background

This library provides conversion from [JSKOS format] to [MARC 21 Authority format].

See [mc2skos](https://github.com/scriptotek/mc2skos) for the reverse conversion.


[JSKOS format]: https://gbv.github.io/jskos/
[MARC 21 Authority format]: http://www.loc.gov/marc/authority/

## Install

Just copy file `src/jskos2marc.php` where needed.

Requires PHP 7 (but might work on PHP 5.6 as well).

## Usage

See [API section below](#api) for details to use in PHP source code:

~~~php
require_once 'src/jskos2marc.php';

$jskos = [ [ 'prefLabel' => [ 'de' => 'Test' ] ] ];
$xml = JSKOS\jskos2marcxml($jskos);
~~~

See script `bin/jskos2marc` to convert JSKOS to MARC from command line:

~~~bash
./bin/jskos2marc $URL $FILENAME ...
~~~

## API

The following PHP functions are defined:

### jskos2marcxml

Convert an array of JSKOS objects to a MARCXML string.

### marcjson2marcxml

Convert an MARC JSON record to a MARCXML string.

### jskos_decode

Decode a JSON string into an array of JSKOS objects.

## License

MIT
