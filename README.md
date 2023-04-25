# Useme new offer notifier
This project provides a convenient way to receive instant notifications when new offers are posted on the [Useme](https://useme.com) platform. The script is designed to scrape the website for new offers and send email alerts with the relevant information. Fetched offers are stored in a local database.

## Installation

1.  **Clone the repository**: Clone this repository to your local machine using `git clone`.
2.  **Create a database**: Set up a local database to store the offers fetched by the script.
3.  **Configure settings**: Copy the `config_sample.php` file to `config.php` and fill in the required information, including database connection credentials and email settings.
4.  **Set up a cron job**: Add the `scraper.php` script to your crontab to run periodically, according to your desired frequency of notifications (e.g., every hour, daily, etc.).
