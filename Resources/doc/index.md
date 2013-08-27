# app/config/config.yml

doctrine:
    dbal:
        ...
        types:
            binary: ITE\DoctrineBundle\Types\BinaryType
            ip_address: ITE\DoctrineBundle\Types\IpAddressType
        mapping_types:
            binary: binary
            ip_address: ip_address
    ...
    orm:
        auto_generate_proxy_classes: "%kernel.debug%"
        entity_managers:
            default:
                auto_mapping: true
                dql:
                    string_functions:
                        IFNULL: DoctrineExtensions\Query\Mysql\IfNull
                        IF: DoctrineExtensions\Query\Mysql\IfElse
                        ...
                        DATE_FORMAT: ITE\DoctrineBundle\Query\Mysql\DateFormat
                    numeric_functions:
                        IFNULL: DoctrineExtensions\Query\Mysql\IfNull
                        IF: DoctrineExtensions\Query\Mysql\IfElse