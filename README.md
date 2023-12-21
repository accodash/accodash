Add `extension=php_zip.dll` to the php.ini before running `composer install`

## Selenium setup

1. Download [Selenium Server jar](https://www.selenium.dev/downloads/). (requires JDK 11)
2. Download [Chromedriver](https://googlechromelabs.github.io/chrome-for-testing/#stable) and unpack it.
3. Put both the Selenium Server jar file and the Chromedriver executable in some accessible folder.
4. Add the folder that stores the Chromedriver executable to the system's PATH environmental variable.
5. Open cmd in the location of your Selenium Server and run `java -jar selenium-server-<version>.jar standalone`.
