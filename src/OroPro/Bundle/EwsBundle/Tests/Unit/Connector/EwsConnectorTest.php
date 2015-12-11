<?php
namespace OroPro\Bundle\EwsBundle\Tests\Unit\Connector;

use OroPro\Bundle\EwsBundle\Connector\EwsConnector;
use OroPro\Bundle\EwsBundle\Connector\Search\SearchQueryBuilder;
use OroPro\Bundle\EwsBundle\Connector\Search\SearchQuery;
use OroPro\Bundle\EwsBundle\Connector\Search\QueryStringBuilder;
use OroPro\Bundle\EwsBundle\Connector\Search\RestrictionBuilder;
use OroPro\Bundle\EwsBundle\Ews\EwsType as EwsType;

class EwsConnectorTest extends \PHPUnit_Framework_TestCase
{
    public function testFindItemsEmptyResult()
    {
        $request = $this->createEmptyFindItemType();
        $response = $this->createEmptyFindItemResponseType();

        $ewsMock = $this->getMockBuilder('OroPro\Bundle\EwsBundle\Ews\ExchangeWebServices')
            ->disableOriginalConstructor()
            ->setMethods(array('FindItem'))
            ->getMock();

        $ewsMock->expects($this->at(0))->method('FindItem')
            ->with($this->equalTo($request))
            ->will($this->returnValue($response));

        $connector = new EwsConnector($ewsMock);
        $items = $connector->findItems(EwsType\DistinguishedFolderIdNameType::INBOX);

        $this->assertCount(1, $items);
        $this->assertEmpty($items[0]->RootFolder->Items->Message);
    }

    public function testFindItemsNonEmptyResult()
    {
        $request = $this->createEmptyFindItemType();
        $response = $this->createNonEmptyFindItemResponseType(array('item1'));

        $ewsMock = $this->getMockBuilder('OroPro\Bundle\EwsBundle\Ews\ExchangeWebServices')
            ->disableOriginalConstructor()
            ->setMethods(array('FindItem'))
            ->getMock();

        $ewsMock->expects($this->at(0))->method('FindItem')
            ->with($this->equalTo($request))
            ->will($this->returnValue($response));

        $connector = new EwsConnector($ewsMock);
        $items = $connector->findItems(EwsType\DistinguishedFolderIdNameType::INBOX);

        $this->assertCount(1, $items);
        $this->assertCount(1, $items[0]->RootFolder->Items->Message);
        $this->assertEquals('item1', $items[0]->RootFolder->Items->Message[0]->ItemId->Id);
    }

    public function testFindItemsWithImpersonation()
    {
        $sid = new EwsType\ConnectingSIDType();
        $sid->PrimarySmtpAddress = 'test';

        $ei = new EwsType\ExchangeImpersonationType();
        $ei->ConnectingSID = $sid;

        $request = $this->createEmptyFindItemType();
        $response = $this->createEmptyFindItemResponseType();

        $ewsMock = $this->getMockBuilder('OroPro\Bundle\EwsBundle\Ews\ExchangeWebServices')
            ->disableOriginalConstructor()
            ->setMethods(array('setImpersonation', 'FindItem'))
            ->getMock();

        $ewsMock->expects($this->at(0))->method('setImpersonation')
            ->with($this->equalTo($ei));
        $ewsMock->expects($this->at(1))->method('FindItem')
            ->with($this->equalTo($request))
            ->will($this->returnValue($response));

        $connector = new EwsConnector($ewsMock);
        $connector->setTargetUser($sid);
        $connector->findItems(EwsType\DistinguishedFolderIdNameType::INBOX);
    }

    public function testFindItemsWithQuery()
    {
        $queryBuilder = new SearchQueryBuilder(
            new SearchQuery(
                new QueryStringBuilder(),
                new RestrictionBuilder()
            )
        );
        $query = $queryBuilder
            ->subject('test')
            ->get();

        $request = $this->createEmptyFindItemType();
        $request->QueryString = $query->convertToQueryString();
        $response = $this->createEmptyFindItemResponseType();

        $configurator = $this->getMockBuilder('OroPro\Bundle\EwsBundle\Provider\EwsServiceConfigurator')
            ->disableOriginalConstructor()
            ->getMock();

        $ewsMock = $this->getMockBuilder('OroPro\Bundle\EwsBundle\Ews\ExchangeWebServices')
            ->setConstructorArgs(array($configurator))
            ->setMethods(array('FindItem'))
            ->getMock();

        $ewsMock->expects($this->at(0))->method('FindItem')
            ->with($this->equalTo($request))
            ->will($this->returnValue($response));

        $connector = new EwsConnector($ewsMock);
        $connector->findItems(EwsType\DistinguishedFolderIdNameType::INBOX, $query);
    }

    private function createEmptyFindItemType()
    {
        $request = new EwsType\FindItemType();
        $request->ItemShape = new EwsType\ItemResponseShapeType();
        $request->ItemShape->BaseShape = EwsType\DefaultShapeNamesType::ID_ONLY;
        $request->Traversal = EwsType\ItemQueryTraversalType::SHALLOW;

        $request->ParentFolderIds = new EwsType\NonEmptyArrayOfBaseFolderIdsType();
        $request->ParentFolderIds->DistinguishedFolderId = array();
        $request->ParentFolderIds->DistinguishedFolderId[] = new EwsType\DistinguishedFolderIdType();
        $request->ParentFolderIds->DistinguishedFolderId[0]->Id = EwsType\DistinguishedFolderIdNameType::INBOX;

        return $request;
    }

    private function createEmptyFindItemResponseType()
    {
        $response = new EwsType\FindItemResponseType();
        $response->ResponseMessages = new EwsType\ArrayOfResponseMessagesType();
        $response->ResponseMessages->FindItemResponseMessage = array();
        $response->ResponseMessages->FindItemResponseMessage[] = new EwsType\FindItemResponseMessageType();
        $response->ResponseMessages->FindItemResponseMessage[0]->RootFolder = new EwsType\FindItemParentType();
        $response->ResponseMessages->FindItemResponseMessage[0]->RootFolder->Items = new EwsType\ArrayOfRealItemsType();

        return $response;
    }

    private function createNonEmptyFindItemResponseType($ids)
    {
        $response = $this->createEmptyFindItemResponseType();
        $response->ResponseMessages->FindItemResponseMessage[0]->RootFolder->Items->Message = array();
        $response->ResponseMessages->FindItemResponseMessage[0]->RootFolder->Items->Message[]
            = new EwsType\MessageType();
        foreach ($ids as $id) {
            $itemId = new EwsType\ItemIdType();
            $itemId->Id = $id;
            $response->ResponseMessages->FindItemResponseMessage[0]->RootFolder->Items->Message[0]->ItemId = $itemId;
        }

        return $response;
    }
}
