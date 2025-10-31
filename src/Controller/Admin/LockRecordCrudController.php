<?php

declare(strict_types=1);

namespace Tourze\IdleLockScreenBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Tourze\IdleLockScreenBundle\Entity\LockRecord;
use Tourze\IdleLockScreenBundle\Enum\ActionType;

/**
 * @extends AbstractCrudController<LockRecord>
 */
#[AdminCrud(
    routePath: '/idle-lock/record',
    routeName: 'idle_lock_record'
)]
final class LockRecordCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return LockRecord::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('锁定记录')
            ->setEntityLabelInPlural('锁定记录')
            ->setSearchFields(['sessionId', 'route', 'ipAddress', 'userAgent'])
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setPaginatorPageSize(30)
            ->showEntityActionsInlined()
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::NEW, Action::EDIT) // 锁定记录通常只读，不允许编辑
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        // 明确指定不同页面的字段，避免EasyAdmin自动推断包含复杂类型字段
        $fields = [];

        if (Crud::PAGE_INDEX === $pageName) {
            $fields[] = IdField::new('id', 'ID');
            $fields[] = AssociationField::new('user', '用户')->setHelp('触发锁定的用户');
            $fields[] = TextField::new('sessionId', '会话ID')->setHelp('用户会话标识符');
            $fields[] = ChoiceField::new('actionType', '操作类型')
                ->setChoices(array_combine(
                    array_map(fn (ActionType $case) => $case->getLabel(), ActionType::cases()),
                    array_map(fn (ActionType $case) => $case->value, ActionType::cases())
                ))
                ->setHelp('锁定操作的类型')
            ;
            $fields[] = TextField::new('route', '路由')->setHelp('触发锁定的页面路由');
            $fields[] = DateTimeField::new('createTime', '创建时间')
                ->setFormat('yyyy-MM-dd HH:mm:ss')
                ->setHelp('记录创建的时间')
            ;
        } elseif (Crud::PAGE_DETAIL === $pageName) {
            $fields[] = IdField::new('id', 'ID');
            $fields[] = AssociationField::new('user', '用户')
                ->setHelp('触发锁定的用户')
                ->setColumns(4)
            ;
            $fields[] = TextField::new('sessionId', '会话ID')
                ->setHelp('用户会话标识符')
                ->setColumns(4)
            ;
            $fields[] = ChoiceField::new('actionType', '操作类型')
                ->setChoices(array_combine(
                    array_map(fn (ActionType $case) => $case->getLabel(), ActionType::cases()),
                    array_map(fn (ActionType $case) => $case->value, ActionType::cases())
                ))
                ->setHelp('锁定操作的类型')
                ->setColumns(4)
            ;
            $fields[] = TextField::new('route', '路由')
                ->setHelp('触发锁定的页面路由')
                ->setColumns(6)
            ;
            $fields[] = TextField::new('ipAddress', 'IP地址')
                ->setHelp('用户的IP地址')
                ->setColumns(3)
            ;
            $fields[] = TextareaField::new('userAgent', '用户代理')
                ->setHelp('浏览器用户代理信息')
                ->setNumOfRows(2)
                ->setColumns(9)
            ;
            $fields[] = TextareaField::new('contextForDisplay', '上下文信息')
                ->setVirtual(true)
                ->onlyOnDetail()
                ->setHelp('额外的上下文数据（JSON格式）')
                ->setNumOfRows(3)
                ->setFormTypeOption('attr', ['readonly' => true])
            ;
            $fields[] = DateTimeField::new('createTime', '创建时间')
                ->setFormat('yyyy-MM-dd HH:mm:ss')
                ->setHelp('记录创建的时间')
                ->setColumns(4)
            ;
        }

        return $fields;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('user')
            ->add('actionType')
            ->add('route')
            ->add('sessionId')
            ->add('ipAddress')
            ->add('createTime')
            // 不添加context字段到过滤器，避免EasyAdmin处理复杂数据类型时出现问题
        ;
    }
}
