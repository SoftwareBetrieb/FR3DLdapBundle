<?php

namespace FR3D\LdapBundle\Tests\Hydrator;

use FR3D\LdapBundle\Tests\DependencyInjection\ConfigurationTrait;
use PHPUnit\Framework\TestCase;

abstract class AbstractHydratorTestCase extends TestCase
{
    use ConfigurationTrait {
        getDefaultUserConfig as parentGetDefaultUserConfig;
    }
    use HydratorInterfaceTestTrait;

    /**
     * Returns default configuration for User subtree.
     *
     * Same as service parameter `fr3d_ldap.ldap_manager.parameters`
     */
    protected function getDefaultUserConfig(): array
    {
        $config = $this->parentGetDefaultUserConfig();
        $config['attributes'][] = [
            'ldap_attr' => 'roles',
            'user_method' => 'setRoles',
        ];

        return $config;
    }
}
