# Authenticon prototype API #

*Authenticon* is a project proposed by [Martus](http://www.martus.org). Briefly, Martus is used by people in hostile environments to transmit evidence of human rights abuses. Their transmissions are encrypted. To detect interception, a 40-digit fingerprint is provided that can be verified out of band (eg, by phone call). Because it is difficult to check a 40-digit number over a phone the fingerprint is rarely checked. The project proposes to provide a more visual view of the fingerprint that is easier to work with.

This repository contains a prototype API initially developed at the [CHI 2016](http://chi2016.acm.org) day of service. A brief video run-through is [here](https://youtu.be/5LxcUCHlwkI).

* How to install the API
• How to call the API
* Encoding methods
* How it works
* Considerations for further development

## How to install the API ##

The following set-up works:

1. Create a Amazon EC2 instance of the type 
```
Ubuntu Server 14.04 LTS (HVM), SSD Volume Type - ami-f95ef58a
```
During creation, add HTTP (port 80) to the security group:
![Screen Shot 2016-05-10 at 04.18.13.png](https://bitbucket.org/repo/6kG9dA/images/1079850076-Screen%20Shot%202016-05-10%20at%2004.18.13.png)

Log into the instance. Install Apache and PHP:

```
sudo apt-get update
sudo apt-get install apache2
sudo apt-get install php5
sudo apt-get install php-pear
sudo apt-get install php5-dev
```

Install ImageMagick and Imagick for PHP:
```
sudo apt-get install pkg-config
sudo apt-get install libmagickwand-dev
sudo pecl install imagick
# Choose autodetect for ImageMagick location
cd /etc/php5/apache2
sudo vim php.ini
# Insert at the end:
# extension=imagick.so
cd /etc/php5/cli
sudo vim php.ini
# Insert at the end:
# extension=imagick.so
```

Install Memcached and the PHP package for it:
```
sudo apt-get install libevent-dev
cd ~
wget http://memcached.org/latest
mv latest latest.tar.gz
tar -zxvf latest.tar.gz
ls
rm latest.tar.gz
cd memcached-1.4.25
./configure && make && make test && sudo make install
sudo apt-get install php5-memcached memcached
```

Install the WSGI module for Apache:
```
sudo apt-get install libapache2-mod-wsgi
```

Install git:
```
sudo apt-get install git
```

Clone this repository:
```
cd /var/www
sudo chmod 777 html
cd html
git clone https://lorenzowood@bitbucket.org/lorenzowood/authenticon-api.git
mv authenticon-api api
```

Connect the example encoder written in Python (see below) using WSGI:
```
cd /etc/apache2/sites-enabled
sudo vim 000-default.conf
# Insert just before </Virtualhost>:
# WSGIScriptAlias /api/encoders/liang /var/www/html/api/encoders/liang/liang.py
```

Finally, restart Apache:
```
sudo service apache2 restart
```

If all has gone well you should be able to call the API as described in the next section.

## How to call the API ##

There is a single endpoint for the API at
```
/api/visualise_fingerprint.php
```

If you call it with no parameters it returns a JSON string containing an array of available encoding methods. For example (spacing added for readability):
```
[
  { "id"         : "3icons-memory",
    "name"       : "3 icons (memory)",
    "description":"Makes a three-icon code and keeps it in store for 24 hours, after which sequences may be reused. Has an absolute capacity of c. 175k different fingerprints per 24 hours.",
    "parts"      : 3,
    "type"       : "image"
  },
  { "id"         :"3words-memory",

...
```

As a client of the API you should perform this no-parameter call to check what encoding methods are currently available. To encode a fingerprint, call the endpoint with GET parameters **method** (corresponding to a method id from the above array) and a **fingerprint** parameter containing a 40-digit number. For example:
```
/api/visualise-fingerprint.php?method=3icons-memory&fingerprint=1234567890123456789012345678901234567890
```

The API returns either a PNG image or plain text, depending on the type indicated in the information array (*image* or *text*).

By default the response is the complete encoding of the fingerprint. However, the information array explains the number of parts into which the response can be split. If you add a **part** parameter to the API call you can return a single part (numbered from 0). This allows you to use serial presentation, for example.

## Encoding methods ##

The prototype provides these encoding methods:

### 10 icons ###
Divides the 40-digit fingerprint into 10 groups of 4 digits and displays one of 10,000 icons for each group of 4 digits.
### 14 icons ###
Divides the 40 digit fingerprint into 14 groups of 3 digits (padding the end with 0s) and displays one of 1,000 icons for each group of 3 digits.
### Liang hierarchy ###
Calculates a 4-digit digest from the whole 40-digit fingerprint and shows in text as "first check". Calculates three 4-digit digests and shows those in text as "second check". Divides the fingerprint into 10 groups of 4 digits and shows those in text as "third check".
### 3 icons (memory) ###
Displays a random set of three words and icons (chosen from a set of 56) in response to a fingerprint. The choice persists for 24 hours, so API calls to this method will return the same response for a given fingerprint for 24 hours since the most recent call (ie, the 24-hour timer is reset on every call). Provided users at both ends of a phone call to verify the fingerprint use the same API, this ought to be a reliable method.
### 3 words (memory) ###
As above, but returns just the words as text.

## How it works ##

The main endpoint loads information about methods from **encoding-methods.json**. If provided with a method and a fingerprint it issues a redirect to another URL which contains the encoder.

The *10 icons* and *14 icons* methods use a single encoder *icon-map.php* which accepts an additional parameter **group** to determine the size of the group of digits. The *10 icons* case uses **group=4**; the *14 icons* case uses **group=3**. Icons are stored in a directory *icons* in the same directory as the encoder. Icons have filenames from *0000.png* up to *9999.png*. A directory *templates* contains base PNG images used to frame the icons and provide padding. The default image puts the icons in rows of 5; you can change this with the **cols** parameter.

The *Liang hierarchy* method was written by a collaborator in Python. The file is in a location consistent with the PHP encoders, but it is called via a WSGI mapping.

The *3 icons* and *3 words* methods use a single encoder *3icons.php*. The icons are listed in a file *icons.josn*:
```
"apple" : { "filename" : "noun_22979_cc.png", "credit" : "By Stephanie Wauters for the Noun Project"},
"arm" : { "filename" : "noun_73495_cc.png", "credit" : "By Nathaniel Smith for the Noun Project"},

...
```
The encoder checks memcached for an entry with the fingerprint as a key. If it finds one, it uses the sequence of words stored therein. If not, it generates a new random sequence of three words and checks whether there is an entry with the sequence as a key; if so, it generates another; if not, it makes such an entry to indicate that this sequence is in use. It then attempts to make an entry with the fingerprint as a key containing the new sequence. This fails if one is already there, which means someone else has got there first; in that case, it uses the one that it found.

Memcached allows an atomic *add* operation that either works or fails because there is already a value with that key. Used in the manner described above it should never allow different values for a given fingerprint. It can cause "orphan" sequences, but these should be rare and would only have a temporary impact on storage and available sequences. All the entries in memcached have a fixed expiry (24 hours). Supplying the parameter **textOnly=true** makes this encoder return the words in plain text instead of the image.

NB because it merely associates a fingerprint with a three-icon sequence, this encoder would work just as well with 40-hex-digit PGP fingerprints or longer sequences.

## Considerations for further development ##
* There code contains no automated tests
* There are only dummy icons for the *10 icons* and *14 icons* methods, which are therefore unsuitable for testing
* There is no exception handling or error reporting: errors will cause silent failures from the end-user perspective
* There is no validation of parameters. For example, the fingerprint is used directly as a key for Memcached without checking whether it meets the requirements of such keys (including absence of spaces)
* There has been no performance checking or optimisation (reading JSON files on every invocation is not very efficient; ImageMagick is known to have memory leaks)
* There has been no testing or even peer review of the update method for Memcached to check that its expected properties (ie, never giving different values for a given fingerprint but possibly allowing orphan sequences) are properly implemented — either for logical reasons or because of implementation issues in Memcached.
* Memcached is fast and easy to use but has no persistence, so a restart of Memcached could cause one fingerprint to produce two different sequences even less than 24 hours apart; consider a persistent server or some other method (eg, an indication that a response from *3icons* or *3words* has generated a new sequence could be checked as part of the verification call — both sides should not have new sequences)