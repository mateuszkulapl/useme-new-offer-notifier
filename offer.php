<?php

class Offer
{
    private $id = null;
    private $title = null;
    private $description = null;
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
    public function save()
    {
        if ($this->new == true) {
            $sth = $this->db->prepare('INSERT INTO useme (id, title,description,url) VALUES(:id, :title,:description,:url)');
            $sth->bindParam(':id', $this->id, PDO::PARAM_INT);
            $sth->bindParam(':title', $this->title, PDO::PARAM_STR);
            $sth->bindParam(':description', $this->description, PDO::PARAM_STR);
            $sth->bindParam(':url', $this->url, PDO::PARAM_STR);
            try {
                $sth->execute();
            } catch (\PDOException $e) {
                print "Error!: " . $e->getMessage() . "<br/>";
                return false;
            }
            return true;
        }
        return false;
    }

    public function getDetails()
    {
        $pageSource = getPage($this->url);
        $dom = new DomDocument('1.0', 'UTF-8');
        $internalErrors = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $pageSource);
        // Restore error level
        libxml_use_internal_errors($internalErrors);
        $finder = new DomXPath($dom);



        $classname = "job-details__main-title";
        $titleNodes = $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $classname ')]");
        $title = ($titleNodes[0]->textContent);
        $this->title = $title;

        $classname = "job-details__main-desc";
        $descNodes = $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $classname ')]");
        $description = ($dom->saveHTML($descNodes[0]));

        $description = preg_replace('/[[:space:]]{2,}/', ' ', $description); //remove multiple spaces
        $this->description = $description;
    }

    public function sendMail()
    {
        global $email_recipient;
        global $email_sender;
        $headers = 'From: ' . $email_sender . ''       . "\r\n" .
            'Reply-To: ' . $email_sender . "\r\n";
        $headers .= "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n X-Mailer: PHP/" . phpversion();
        $message = "<html><head><title>" . $this->title . "</title></head><body>";

        $message .= '<h1>' . $this->title . '</h1>';
        $message .= $this->description;
        $message .= '<p></br><a href="' . $this->url . '">' . $this->url . '</a>';
        $message .= "<p></br>Wiadomość wysłana automatycznie " . date("d.m.Y H:i") . "</p>";
        
        $message .= "</body></html>";

        $subject = 'Useme nowa oferta: ' . $this->title;
        mail($email_recipient, $subject, $message, $headers);
    }
}
