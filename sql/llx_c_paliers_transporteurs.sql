CREATE TABLE llx_c_paliers_transporteurs (
	rowid int(11) AUTO_INCREMENT PRIMARY KEY,
	fk_trans int(11) NOT NULL,
	poids FLOAT NOT NULL,
	active int(11) NOT NULL
) ENGINE = InnoDB;
