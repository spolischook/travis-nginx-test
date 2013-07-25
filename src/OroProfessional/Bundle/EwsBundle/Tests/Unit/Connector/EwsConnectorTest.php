<?php
namespace OroProfessional\Bundle\EwsBundle\Tests\Unit\Connector;

use OroProfessional\Bundle\EwsBundle\Connector\EwsConnector;
use OroProfessional\Bundle\EwsBundle\Connector\Search\SearchQueryBuilder;
use OroProfessional\Bundle\EwsBundle\Connector\Search\SearchQuery;
use OroProfessional\Bundle\EwsBundle\Connector\Search\QueryStringBuilder;
use OroProfessional\Bundle\EwsBundle\Connector\Search\RestrictionBuilder;
use OroProfessional\Bundle\EwsBundle\Ews\EwsType as EwsType;

class EwsConnectorTest extends \PHPUnit_Framework_TestCase
{
    public function testFindItemsEmptyResult()
    {
        $request = $this->createEmptyFindItemType();
        $response = $this->createEmptyFindItemResponseType();

        $ewsMock = $this->getMockBuilder('OroProfessional\Bundle\EwsBundle\Ews\ExchangeWebServices')
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

        $ewsMock = $this->getMockBuilder('OroProfessional\Bundle\EwsBundle\Ews\ExchangeWebServices')
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

        $ewsMock = $this->getMockBuilder('OroProfessional\Bundle\EwsBundle\Ews\ExchangeWebServices')
            ->disableOriginalConstructor()
            ->setMethods(array('SetImpersonation', 'FindItem'))
            ->getMock();

        $ewsMock->expects($this->at(0))->method('SetImpersonation')
            ->with($this->equalTo($ei));
        $ewsMock->expects($this->at(1))->method('FindItem')
            ->with($this->equalTo($request))
            ->will($this->returnValue($response));

        $connector = new EwsConnector($ewsMock);
        $connector->findItems(EwsType\DistinguishedFolderIdNameType::INBOX, $sid);
    }

    public function testFindItemsWithQuery()
    {
        $queryBuilder = new SearchQueryBuilder(
            new SearchQuery(
                new QueryStringBuilder(),
                new RestrictionBuilder()
            ));
        $query = $queryBuilder
            ->subject('test')
            ->get();

        $request = $this->createEmptyFindItemType();
        $request->QueryString = $query->convertToQueryString();
        $response = $this->createEmptyFindItemResponseType();

        $ewsMock = $this->getMockBuilder('OroProfessional\Bundle\EwsBundle\Ews\ExchangeWebServices')
            ->setConstructorArgs(array('wsdl', '', '', '', EwsType\ExchangeVersionType::EXCHANGE2010))
            ->setMethods(array('FindItem'))
            ->getMock();

        $ewsMock->expects($this->at(0))->method('FindItem')
            ->with($this->equalTo($request))
            ->will($this->returnValue($response));

        $connector = new EwsConnector($ewsMock);
        $connector->findItems(EwsType\DistinguishedFolderIdNameType::INBOX, null, $query);
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
        $response->ResponseMessages->FindItemResponseMessage[0]->RootFolder->Items->Message[] = new EwsType\MessageType();
        foreach ($ids as $id) {
            $itemId = new EwsType\ItemIdType();
            $itemId->Id = $id;
            $response->ResponseMessages->FindItemResponseMessage[0]->RootFolder->Items->Message[0]->ItemId = $itemId;
        }

        return $response;
    }
}
