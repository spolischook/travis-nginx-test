<?php

namespace OroCRMPro\Bundle\OutlookBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Route("/outlook")
 */
class OutlookController extends Controller
{
    /**
     * @return Response
     *
     * @Template()
     * @Route(
     *      "/download_addin",
     *      name="orocrmpro_outlook_download_addin"
     * )
     */
    public function downloadAddinAction()
    {
        return [
            'files' => $this->getFiles()
        ];
    }

    /**
     * @return array
     */
    public function getFiles()
    {
        $result = [];
        $files = $this->get('orocrmpro_outlook.addin_manager')->getFiles();
        foreach ($files as $file) {
            $item = ['url' => $file['url']];
            if (!empty($file['doc_url'])) {
                $item['doc_url'] = $file['doc_url'];
            }
            $result[$file['name']] = $item;
        }

        return $result;
    }
}
