# Poem Generator ("simile")

A new project for ARTSTATION in 2020: generating postcards from UGC with various AI APIs, codenamed "simile".

*This repo contains the public release of the project, some information has been changed or redacted.*


## Contents

This repo contains two main folders, "website" which contains the core of the site, and "pull_poems" which is a tool for ripping poems from PoetryDB for training.

Within "website" all scripts for simile are present, including a composer script to install the Google APIs for image recognition.

If releasing publicly, the "backend" folder and appropriate API endpoints should be privated as they allow direct access to the database.

Read the steps below to configure a server to host this application. A demo is available [here](https://simile.mattfiler.co.uk/).


## Setup

- Install Anaconda into "/opt/"
- Set Anaconda as default Python, and enable `conda` command
- `pip install markovify`
- `pip install pronouncing`
- `conda install keras`
- Enable CGI within Apache2 & set it up for "python" folder
- Upload all "website" files
- Install dos2unix
- `cd` into uploaded website directory
- `dos2unix python/generator/generator.py`
- `chmod 755 python/generator/generator.py`
- Install composer
- `composer install`
- Create MySQL database from given SQL file ("Artstation2020.sql")
- Create MySQL user and update password and username in "website/php/shared.php"
- Update base URL in "website/php/shared.php"
- Sign up for RapidAPI and enable WordsAPI, add key to "website/php/shared.php"
- Sign up for Google Vision API and download the connection JSON file
- Update the path to this JSON file in "website/php/shared.php"


## Training

To train the network, generator_local.py can be used in the "website/python/generator" folder. Run this script using the same Anaconda environment described in the setup process.

With the sample poetry to train on, place the same text file in the "input" folder.

You can provide the artist's name (should be the text file's name), or alternatively re-run the script on all artists in the "input" folder.
