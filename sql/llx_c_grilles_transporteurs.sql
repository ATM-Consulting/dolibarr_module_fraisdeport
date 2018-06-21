CREATE TABLE llx_c_grilles_transporteurs (
	rowid int(11) AUTO_INCREMENT PRIMARY KEY,
	transport VARCHAR(20) NOT NULL,
	fk_pays int(11) NOT NULL,
	departement INT NOT NULL,
	poids FLOAT NOT NULL,
	tarif FLOAT NOT NULL,
	active int(11) NOT NULL
) ENGINE = InnoDB;
