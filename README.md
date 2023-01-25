# Gennev community pages
## Structure
* Frontend files are off the root (/public, /src)
* Backend files are in the /backend folder

## Installation
Please stand up a PHP/Apache/MySQL server and point the Document root to the backend folder.

Update the database credentials in the `backend/src/config.php`

Import the `backend/data/gennev.sql` into the database (this is the structure only).

Open the following URL in a browser: <http://localhost/init>; 
 this will load the sample data from `backend/data/sample-data.txt` - feel free to alter that file before importing

Also, please update the `src/config.json` file with the URL of the backend webserver 

