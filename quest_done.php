<?php

require_once(dirname(__FILE__) . '/' . 'vendor/autoload.php');

/** @var Statistic $with_documents */
/** @var Statistic $without_documents */

abstract class Statistic
{
    public $count;
    public $sum;
}

/**
 * @brief Statistics on the documents
 */
class Quest extends \Apo100l\Quest\QuestAbstract {
    private $start = null;
    private $end   = null;

    /**
     * @brief Set start date
     *
     * @param $date string
     */
    public function setStartDate($date)
    {
        $this->start = $date;
    }

    /**
     * @brief Set end date
     *
     * @param $date string
     */
    public function setEndDate($date)
    {
        $this->end = $date;
    }

    /**
     * @brief Get dates from the command line
     * @return string
     */
    public static function getInputDate()
    {
        $handle = fopen('php://stdin', 'r');
        $is_first = null;

        do {
            if ($is_first !== null) {
                echo "Date format: YYYY-MM-DD, or type exit\n";
            }

            $line = trim(fgets($handle));

            if ($line === 'exit') {
                echo 'Bye';
                exit();
            }
        } while(!preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/i', $line, $is_first));

        fclose($handle);

        return $line;
    }

    /**
     * @brief Calculate the number of payments
     *
     * @param bool $documents True - with documents, false - without documents
     *
     * @return mixed
     */
    public function getPayments($documents = true)
    {
        $pdo = $this->getDb()->query(
            'SELECT COUNT(*) as `count`, SUM(payments.amount) as `sum` FROM payments ' .
                'WHERE ' .
                    '( payments.create_ts BETWEEN ' . $this->getDb()->quote($this->start) . ' AND ' . $this->getDb()->quote($this->end) . ') AND ' .
                    ($documents == false ? 'NOT' : '') . ' EXISTS (SELECT * FROM documents WHERE documents.entity_id=payments.id)');
        $pdo->setFetchMode(PDO::FETCH_OBJ);

        return $pdo->fetch();
    }

    /**
     * @brief Close connection
     */
    public function close()
    {
        $this->setDb(null);
    }
}

if (in_array('statistic', $argv)) {
    echo 'Please enter start date: ' . "\n";

    $start = Quest::getInputDate();

    echo 'Please enter end date: ' . "\n";

    $end = Quest::getInputDate();

    echo "\n";

    $quest = new Quest();
    $quest->setStartDate($start);
    $quest->setEndDate($end);

    echo '+-------+---------+' . "\n";
    echo '| count | amount  |' . "\n";
    echo '+-------+---------+' . "\n";

    if (in_array('--with-documents', $argv)) {
        $with_documents = $quest->getPayments(true);
        echo '| ' . str_pad($with_documents->count, 5) . ' | ' . str_pad($with_documents->sum, 7) . ' |' . "\n";
    }

    if (in_array('--without-documents', $argv)) {
        $without_documents = $quest->getPayments(false);
        echo '| ' . str_pad($without_documents->count, 5) . ' | ' . str_pad($without_documents->sum, 7) . ' |' . "\n";
    }

    echo '+-------+---------+' . "\n";

    $quest->close();
}