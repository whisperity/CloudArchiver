CloudArchiver
==============
Licence
---------
Original code is licenced under GNU General Public License, version 3 available here: http://www.gnu.org/licenses/gpl-3.0-standalone.html

My code (everything committed by Cloud Chiller/steamruler after commit 7[7cb6d4b65d82d79af4e4f55c3063c91b55f39a3](https://github.com/steamruler/CloudArchiver/commit/77cb6d4b65d82d79af4e4f55c3063c91b55f39a3)) is licenced under the very loose [Tiny Driplet Licence](http://cloudchiller.net/#Tiny%20Driplet%20Licence), made by yours truly.

About this project
---------
This project is based on [emoose's 4chan-archiver](https://github.com/emoose/4chan-archiver). Emoose's archiver is lightweight but has next to no security since it is made as a private system with its one-user login.
There are plenty of exploits and bugs, you can do an SQL injection in almost all input fields. My archiver is heavier but has increased security and is actively developed. It is prettier too :)

![CloudArchiver GUI](http://i.imgur.com/GTflO.png)

Features
---------

* Fully parse and download any thread
* Very small overhead
* MySQL powered login system with SHA512 encryption.
* Actually works without errors!

Requires
---------

* PHP 5.1.0 or newer
* MySQL
* Apache

Installation
-------------

1. Import cloudarchiver.sql (Inside the Setup Files folder) into a Database.
2. Setup config.php with your paths and MySQL info.
3. Windows Users can import CloudArchiver_Task.xml while Linux users can create a cronjob.