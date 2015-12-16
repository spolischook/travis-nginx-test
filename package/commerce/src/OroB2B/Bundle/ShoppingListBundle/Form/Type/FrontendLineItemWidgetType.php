<?php

namespace OroB2B\Bundle\ShoppingListBundle\Form\Type;

use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;

class FrontendLineItemWidgetType extends AbstractType
{
    const NAME = 'orob2b_shopping_list_frontend_line_item_widget';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var AccountUser
     */
    private $accountUser;

    /**
     * @var string
     */
    protected $shoppingListClass;

    /**
     * @var TokenStorage
     */
    protected $tokenStorage;

    /**
     * @param ManagerRegistry $registry
     * @param TokenStorage    $tokenStorage
     */
    public function __construct(ManagerRegistry $registry, TokenStorage $tokenStorage)
    {
        $this->registry = $registry;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'shoppingList',
                'entity',
                [
                    'required' => false,
                    'label' => 'orob2b.shoppinglist.lineitem.shopping_list.label',
                    'class' => $this->shoppingListClass,
                    'query_builder' => function (ShoppingListRepository $repository) {
                        return $repository->createFindForAccountUserQueryBuilder($this->getAccountUser());
                    },
                    'empty_value' => 'orob2b.shoppinglist.lineitem.create_new_shopping_list',
                ]
            )
            ->add(
                'shoppingListLabel',
                'text',
                [
                    'mapped' => false,
                    'required' => true,
                    'label' => 'orob2b.shoppinglist.lineitem.new_shopping_list_label'
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        /** @var ShoppingListRepository $shoppingListRepository */
        $shoppingListRepository = $currentShoppingList = $this->registry
            ->getManagerForClass($this->shoppingListClass)
            ->getRepository($this->shoppingListClass);
        $currentShoppingList = $shoppingListRepository->findCurrentForAccountUser($this->getAccountUser());

        $view->children['shoppingList']->vars['currentShoppingList'] = $currentShoppingList;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'constraints' => [
                    new Callback([
                        'groups' => ['add_product'],
                        'methods' => [[$this, 'checkShoppingListLabel']]
                    ])
                ]
            ]
        );
    }

    /**
     * @param LineItem $data
     * @param ExecutionContextInterface $context
     */
    public function checkShoppingListLabel($data, ExecutionContextInterface $context)
    {
        if (!$data->getShoppingList()) {
            $context->buildViolation('orob2b.shoppinglist.not_empty')
                ->atPath('shoppingListLabel')
                ->addViolation();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return FrontendLineItemType::NAME;
    }

    /**
     * @param string $shoppingListClass
     */
    public function setShoppingListClass($shoppingListClass)
    {
        $this->shoppingListClass = $shoppingListClass;
    }

    /**
     * @return string|AccountUser
     */
    protected function getAccountUser()
    {
        if (!$this->accountUser) {
            $token = $this->tokenStorage->getToken();
            if ($token) {
                $this->accountUser = $token->getUser();
            }
        }

        if (!$this->accountUser || !is_object($this->accountUser)) {
            throw new AccessDeniedException();
        }

        return $this->accountUser;
    }
}
