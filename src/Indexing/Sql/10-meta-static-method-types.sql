CREATE TABLE meta_static_method_types(
    id                    integer NOT NULL PRIMARY KEY AUTOINCREMENT,
    file_id               integer,

    fqcn                  varchar(255) NOT NULL,
    name                  varchar(255) NOT NULL,

    argument_index        int(1) NOT NULL,

    value                 varchar(512) NOT NULL,
    value_node_type       varchar(512) NOT NULL,

    return_type           varchar(512) NOT NULL,

    FOREIGN KEY(file_id) REFERENCES files(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);
