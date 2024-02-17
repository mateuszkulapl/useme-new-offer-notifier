<?php

class Offer
{
    private $id = null;
    private $title = null;
    private $description = null;
    private $budget = null;
    private $employer_name = null;
    private $add_time = null;
    private $url = null;
    private $new = null;
    private $db = null;


    public function __construct($db, $url, $id)
    {
        $this->db = $db;
        $this->url = $url;
        $this->id = $id;
        $this->new = !($this->isInDatabase());
    }


    public function isInDatabase()
    {
        $sth = $this->db->prepare("SELECT COUNT(*) AS `total` FROM useme WHERE id = :id");
        $sth->bindParam(':id', $this->id, PDO::PARAM_INT);
        $sth->execute();
        $result = $sth->fetchObject();
        if ($result->total > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function isNew()
    {
        return $this->new;
    }

    /**
     * Save offer to database
     *
     * @return bool true if success, false if error
     */
    public function save()
    {
        if ($this->new != true)
            return false;

        $sth = $this->db->prepare('INSERT INTO useme (id, title,description,budget,employer_name,url) VALUES(:id, :title,:description,:budget,:employer_name,:url)');
        $sth->bindParam(':id', $this->id, PDO::PARAM_INT);
        $sth->bindParam(':title', $this->title, PDO::PARAM_STR);
        $sth->bindParam(':description', $this->description, PDO::PARAM_STR);
        $sth->bindParam(':budget', $this->budget, PDO::PARAM_STR);
        $sth->bindParam(':employer_name', $this->employer_name, PDO::PARAM_STR);
        $sth->bindParam(':url', $this->url, PDO::PARAM_STR);
        try {
            $sth->execute();
        } catch (\PDOException $e) {
            print "Error!: " . $e->getMessage() . "<br/>";
            return false;
        }
        return true;
    }

    /**
     * Get offer details from page
     *
     * @return void
     */
    public function getDetails()
    {
        $pageSource = getPage($this->url);
        $dom = new DomDocument('1.0', 'UTF-8');
        $internalErrors = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $pageSource);
        // Restore error level
        libxml_use_internal_errors($internalErrors);
        $finder = new DomXPath($dom);
        $titleClassname = "job-details__main-title";
        $titleNodes = $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $titleClassname ')]");
        $title = ($titleNodes[0]->textContent);
        $this->title = $title;

        $descClassname = "job-details__main-desc";
        $descNodes = $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $descClassname ')]");
        $description = ($dom->saveHTML($descNodes[0]));

        $description = preg_replace('/[[:space:]]{2,}/', ' ', $description); //remove multiple spaces
        $this->description = $description;

        $budgetXPath = './/*[contains(concat(" ",normalize-space(@class)," ")," overview__item--budget ")]/p/span';
        $budgetNodes = $finder->query($budgetXPath);
        $this->budget =  $budgetNodes[0]->textContent;

        $employerNameXPath = './/*[contains(concat(" ",normalize-space(@class)," ")," job-details__main ")]//*[contains(concat(" ",normalize-space(@class)," ")," employer__name ")]';
        $employerNameNodes = $finder->query($employerNameXPath);
        $this->employer_name = $employerNameNodes[0]->textContent;

    }

    public function sendMail()
    {
        global $email_recipient;
        global $email_sender;

        ini_set('default_charset', 'UTF-8');
        $preferences = ['input-charset' => 'UTF-8', 'output-charset' => 'UTF-8'];
        
        $subject = 'Useme nowa oferta: ' . $this->title;
        $encoded_subject = iconv_mime_encode('Subject', $subject, $preferences);
        $encoded_subject = substr($encoded_subject, strlen('Subject: '));

        $headers = 'From: ' . $email_sender . ''       . "\r\n" .
            'Reply-To: ' . $email_sender . "\r\n";
        $headers .= "MIME-Version: 1.0" . "\r\n";

        $boundary = uniqid('np');
        $headers .= "Content-type: multipart/alternative; boundary=$boundary" . "\r\n";
    
        $htmlVersion = $this->getHtmlVersion();
        $plainTextVersion= $this->getPlainTextVersion();

        $message = "--$boundary\r\n";
        $message .= "Content-type: text/plain; charset=UTF-8\r\n";
        $message .= "Content-transfer-encoding: 8bit\r\n\r\n";
        $message .= $plainTextVersion . "\r\n";
        
        $message .= "--$boundary\r\n";
        $message .= "Content-type: text/html; charset=UTF-8\r\n";
        $message .= "Content-transfer-encoding: 8bit\r\n\r\n";
        $message .= $htmlVersion . "\r\n";
        $message .= "--$boundary--";
        mail($email_recipient, $encoded_subject, $message, $headers);
    }

    private function getHtmlVersion()
    {
        $htmlVersion = "<html><head><meta charset='UTF-8'></head><body>";
        $htmlVersion .= "<h1>" . $this->title . "</h1>";
        $htmlVersion .= $this->description;
        $htmlVersion .= "<h2>Budżet: " . $this->budget . "</h2>";
        $htmlVersion .= "<h2>Zleceniodawca: " . $this->employer_name . "</h2>";
        $htmlVersion .= '<p></br><a href="' . $this->url . '">' . $this->url . '</a>';
        $htmlVersion .= "<p>Wiadomość wysłana automatycznie " . date("d.m.Y H:i") . "</p>";
        $htmlVersion .= "</body></html>";
        return $htmlVersion;
    }
    private function getPlainTextVersion()
    {
        $plainTextVersion = "" . $this->title . "\r\n";
        $plainTextVersion .= "" . strip_tags($this->description)."\r\n";
        $plainTextVersion .= "Budżet: " . $this->budget . "\r\n";
        $plainTextVersion .= "Zleceniodawca: " . $this->employer_name . "\r\n";
        $plainTextVersion .= "" . $this->url."\r\n";
        $plainTextVersion .= "Wiadomość wysłana automatycznie " . date("d.m.Y H:i") . "\r\n";
        return $plainTextVersion;
    }

}
