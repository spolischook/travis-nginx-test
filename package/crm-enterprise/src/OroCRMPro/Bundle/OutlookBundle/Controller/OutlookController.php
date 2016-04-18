<?php

namespace OroCRMPro\Bundle\OutlookBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Asset\Packages as AssetHelper;

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
        $source = [
            'path' => $this->getParameter('assetic.read_from') . '/bundles/orocrmprooutlook/files/*.*',
            'url'  => 'bundles/orocrmprooutlook/files'
        ];

        /** @var AssetHelper $assetHelper */
        $assetHelper = $this->get('assets.packages');

        $resources       = [];
        $finder          = new Finder();
        $pathParts       = explode('/', $source['path']);
        $fileNamePattern = array_pop($pathParts);
        $files           = $finder->name($fileNamePattern)->in(implode(DIRECTORY_SEPARATOR, $pathParts));
        /** @var \SplFileInfo[] $files */
        foreach ($files as $file) {
            $resources[$file->getFilename()] = $assetHelper->getUrl(
                rtrim($source['url'], '/') . '/' . $file->getFilename()
            );
        }

        return $resources;
    }
}
