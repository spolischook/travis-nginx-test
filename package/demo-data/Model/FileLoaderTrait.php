<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Model;

use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

trait FileLoaderTrait
{

    /**
     * Get Data path directory
     *
     * @return string
     */
    abstract protected function getDataDirectory();

    /**
     * @param $name
     * @return mixed
     */
    protected function loadData($name)
    {
        static $data = [];
        if (!isset($data[$name])) {
            $path = $this->getDataDirectory() . $name;
            $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

            $data[$name] = $this->loadDataFromCSV($path);
        }

        return $data[$name];
    }

    /**
     * @param $path
     * @return array
     * @throws FileNotFoundException
     * @throws NoSuchPropertyException
     */
    protected function loadDataFromCSV($path)
    {
        if (!file_exists($path)) {
            throw new FileNotFoundException($path);
        }

        $data    = [];
        $handle  = fopen($path, 'r');
        $headers = fgetcsv($handle, 3000, ',');

        if (empty($headers)) {
            return [];
        }
        $headers = array_map('strtolower', $headers);
        if (!in_array('uid', $headers)) {
            throw new NoSuchPropertyException('Property: "uid" does not exists');
        }
        while ($info = fgetcsv($handle, 3000, ',')) {
            if (count($info) !== count($headers)) {
                continue;
            }
            $tempData = array_combine($headers, array_values($info));
            if ($tempData) {
                $data[] = $tempData;
            }
        }
        fclose($handle);

        return $data;
    }
}
