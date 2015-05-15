<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Model;

trait GenerateDate
{

    /**
     * Generate Created date
     *
     * @return \DateTime
     */
    protected function generateCreatedDate()
    {
        // Convert to timetamp
        $min = strtotime('now - 2 months');
        $max = strtotime('now - 1 day');
        $val = rand($min, $max);

        $date = date('Y-m-d H:i:s', $val);
        return new \DateTime($date, new \DateTimeZone('UTC'));
    }

    /**
     * Generate Updated date
     *
     * @param \DateTime $created
     * @return \DateTime
     */
    protected function generateUpdatedDate(\DateTime $created)
    {
        // Convert to timetamp
        $min = strtotime($created->format('Y-M-d H:i:s'));
        $max = strtotime('now - 1 day');
        $val = rand($min, $max);

        $date = date('Y-m-d H:i:s', $val);
        return new \DateTime($date, new \DateTimeZone('UTC'));
    }

    /**
     * @param \DateTime $fromDate
     * @return \DateTime
     */
    protected function generateCloseDate(\DateTime $fromDate)
    {
        $closeDate    = clone $fromDate;
        $amountOfDays = rand(1, 30);
        return $closeDate->add(new \DateInterval(sprintf('P%dD', $amountOfDays)));
    }
}
