<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

trait GenerateDate {

    /**
     * Generate Created date
     * @return \DateTime
     */
    protected function generateCreatedDate()
    {
        // Convert to timetamps
        $min = strtotime('now - 1 month');
        $max = strtotime('now - 1 day');
        $val = rand($min, $max);

        $date = date('Y-m-d H:i:s', $val);
        return new \DateTime($date, new \DateTimeZone('UTC'));
    }

    /**
     * Generate Updated date
     * @param \DateTime $created
     * @return \DateTime
     */
    protected function generateUpdatedDate(\DateTime $created)
    {
        // Convert to timetamps
        $min = strtotime($created->format('Y-M-d H:i:s'));
        $max = strtotime('now - 1 day');
        $val = rand($min, $max);

        $date = date('Y-m-d H:i:s', $val);
        return new \DateTime($date, new \DateTimeZone('UTC'));
    }

}