CREATE TABLE llx_c_tarifs_transporteurs (
	rowid int(11) AUTO_INCREMENT PRIMARY KEY,
	fk_palier int(11) NOT NULL,
	fk_pays int(11) NOT NULL,
	departement VARCHAR(20) NOT NULL,
	zipcode VARCHAR(20),
	tarif FLOAT NOT NULL,
	active int(11) NOT NULL
) ENGINE = InnoDB;
