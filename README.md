CloudArchiver
==============
Based on https://github.com/emoose/4chan-archiver
---------
Uses the GNU General Public License, version 3 available here: http://www.gnu.org/copyleft/gpl.html

These small scripts let you create your own little 4chan archive, without needing to use crappy advert ridden websites! (or overly worked on perl scripts, this is 4 hours work)

Features:
---------

* Fully parse and download any thread
* Very small overhead
* MySQL powered login system with SHA512 encryption.
* Actually works without errors!

Requires:
---------

* PHP 4+
* MySQL
* Windows Server 2008 (Preferably running XAMMP)

Installation:
-------------

1. Import cloudarchiver.sql (Inside the Setup Files folder) into a Database.
2. Setup config.php with your paths and MySQL info.
3. Import and make edits to the task (CloudArchiver_Cronjob.xml inside the Setup Files).

Any bugs? Post on the github!
https://github.com/steamruler/CloudArchiver
