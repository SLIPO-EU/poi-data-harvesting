# POI Data Harversting (PDH)

Enable and facilitate the extraction and retrieval of third-party POI data, including the extraction of structured, semi-structured and unstructured POI data from heterogeneous sources and formats.

## Overview

Three different harvesters are provided: PDH-HTML, PDH-API and PDH-CKAN.
Each one is responsible for harvesting a specific type of data source, as explained in detail below, thus jointly covering a large number of data sources existing on the Web.
Briefly, PDH-HTML can be used to extract information from Web pages, PDH-API to retrieve data from sources offering a RESTful Web API and PDH-CKAN to collect data from open data catalogues operating on the CKAN platform.

* **PDH-HTML**: Addresses POI data extraction from unstructured sources, in particular, Web pages.
This includes various Web sites, for example portals for entertainment, food, etc.
It operates in two main stages.
For a given Web site, it first generates a list with the URLs of the Web pages containing the data we are trying to collect.
Then, the harvester retrieves the HTML code of each Web page, and parses it to extract the relevant POI data.
For both stages, configuration provided by the user is required.
Inevitably, this requires that the user has some basic knowledge about HTML in order to provide information about the HTML structure and setup of the pages to be parsed.
All the required pieces of information are provided to the harvester in terms of a comprehensive configuration file in JSON format.

* **PDH-API**: Used to retrieve data from sources offering an HTTP RESTful API.
Although the details of each API may of course differ from case to case, we have found that the same generic pattern is observed in several POI data sources.

* **PDH-CKAN**: Addresses the case of data catalogues, in particular ones using the CKAN open source management system [CKAN].
We have chosen to focus on CKAN-based catalogues, since this covers a very large portion of the open data catalogues available online [ODM].
The harvester in this case is configured to search with tags and/or a query string, in order to retrieve data from the catalogue.
It can then update any previously collected data with the newly retrieved ones.


## Execution

### Requirements

PHP (version at least 7.1 is recommended) with Client URL Library ([cURL](http://php.net/manual/en/book.curl.php)) and Document Object Model ([DOM](http://php.net/manual/en/book.dom.php)) is required.

[PHP Tidy](http://php.net/manual/en/book.tidy.php) is recommended, but is optional.

### Configuration file

In order to run any of the harvesters, a configuration file in `JSON` format is required. Some configuration file examples could be found inside `./conf`.
The essential options, common for all cases are the following:

* **type** One of `web` or `list` for PDH-HTML, `api` for PDH-API or `ckan` for PDH-CKAN. For the case of PDH-HTML, the additional option `list` is used for the case of harvesting a list with URLs which will lead in a second step to the actual harvesting. In this latter case, the output is a JSON file.

* **filename**: The (full or relative) path to the output filename. Its extension would determine the output type (one of `csv`, `json` or `geojson`).

* **url**: The URL which would provide the source of harvesting (in many cases the list of URLs). In the case of PDH-HTML, this `url` could correspond in a local JSON file.

* **base_url**: In case the URL list contains relative paths, this setting provides the base URL to prepend in the elements of the list.

* **max_connections**: The maximum number of simultaneous connections (default: 5).

Many other settings are required, depending on the harvesting type. Details could be found in the comments of the configuration files examples for each case.

### Running

In the command line run:

```bash
./harvest <configuration_file>
```

Adjust the configuration files found in `conf` directory according to your specific needs.

### Output

The output of the harvesting process is a file of one of the following types: `CSV`, `JSON` or `GEOJSON`. The path and the name of the output file is determined in the configuration file under the key `filename`. Accordingly, the filetype is determined from this file extension. Any other extension would lead to failure with a corresponding error message.