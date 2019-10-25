# BabyDol
Baby Dolmetscher - use Google Services like Text2Speech, Natural Language, Custom Image Search and HTML5 MediaStreamRecorder

This tool let you:
 - record your voice usign the HTML5 MediaStreamRecorder,
 - transcribe it using Google's Cloud Speech-to-Text API
 - analyse and segment it using Google's Cloud Natural Language API
 - search for each word and display the result from Google's Image Search
 - read the transcript back to you using your browsers SpeechSynthesisUtterance feature

## Preparation
You will need a web server and PHP>=7
If you don't have it yet [get composer](https://getcomposer.org/doc/00-intro.md)

You need to create two API KEY Credentials from Google's API and Cloud Console:

First a service account for the google cloud services:
Go to: https://console.cloud.google.com/iam-admin/serviceaccounts

The service account need at least the following permissions:
`iam.serviceAccounts.actAs`
You can either create your own role for this or use an existing one like `roles/iam.serviceAccountUser` aka `Dienstkontonutzer`.

Next you need an API Key for the google APIs
Go to: https://console.developers.google.com/apis/credentials?folder&organizationId

Select the following APIs:
- Custom Search API
- Cloud Natural Language API
- Cloud Speech-to-Text API
- Cloud Text-to-Speech API

Add further Limits as you wish. Please note HTTP-Referrer will not work, due to server to server communications.

Also Usage of cloud services and the Custom Search API is subject to billing - but you will have a fair amount of free queries each month. Just keep in mind before you publish your app.

## Installation
- `git clone` the repository in a directory of your choice.
- go to that directory and install the latest dependencies by executing `php composer.phar install`
- execute `php composer.phar install` twice in order to let the post-install-cmd script kick in.
- you should have gotten two json files from the Preparation step - copy them into the [config](config) folder
- open the [server.php](web/server.php) file and adapt the name of your project and the path of the credential files
- you may change the LANGUAGE_CODE there as well - see supported languages in the [natural language documentation](https://cloud.google.com/natural-language/)
- the [web](web) directory should be used as document root by your webserver
- once you see the website follow the instructions there

## Usage

![image](https://user-images.githubusercontent.com/6115324/67603241-a6e9ff00-f778-11e9-9c9c-6a39a84004f0.png)
