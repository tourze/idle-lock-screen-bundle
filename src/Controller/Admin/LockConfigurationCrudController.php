<?php

declare(strict_types=1);

namespace Tourze\IdleLockScreenBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Tourze\IdleLockScreenBundle\Entity\LockConfiguration;

/**
 * @extends AbstractCrudController<LockConfiguration>
 */
#[AdminCrud(
    routePath: '/idle-lock/configuration',
    routeName: 'idle_lock_configuration'
)]
final class LockConfigurationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return LockConfiguration::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('锁定配置')
            ->setEntityLabelInPlural('锁定配置')
            ->setSearchFields(['routePattern', 'description'])
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setPaginatorPageSize(20)
            ->showEntityActionsInlined()
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->onlyOnIndex();

        yield TextField::new('routePattern', '路由模式')
            ->setHelp('支持通配符和正则表达式，例如：/admin/* 或 ^/api/.*$')
            ->setRequired(true)
            ->setColumns(6)
        ;

        yield IntegerField::new('timeoutSeconds', '超时时间（秒）')
            ->setHelp('用户无操作多少秒后触发锁定')
            ->setRequired(true)
            ->setColumns(3)
        ;

        yield BooleanField::new('isEnabled', '启用状态')
            ->setHelp('是否启用此锁定配置')
            ->setColumns(3)
        ;

        yield TextareaField::new('description', '配置描述')
            ->setHelp('描述此配置的用途和适用场景')
            ->setNumOfRows(3)
            ->setColumns(12)
            ->hideOnIndex()
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->onlyOnIndex()
            ->setColumns(4)
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->onlyOnIndex()
            ->setColumns(4)
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('routePattern')
            ->add('isEnabled')
            ->add('timeoutSeconds')
            ->add('createTime')
            ->add('updateTime')
        ;
    }
}
