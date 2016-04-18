<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Model;

trait WeekendCheckerTrait
{
    /**
     * @param \DateTime $dateTime
     * @return bool
     */
    protected function isWeekend(\DateTime $dateTime)
    {
        return in_array($dateTime->format('w'), [0, 6]);
    }
}
