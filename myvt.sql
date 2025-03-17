CREATE TABLE etudiant(
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(30) NOT NULL,
    prenom VARCHAR(30) NOT NULL,
    cin VARCHAR(30) NOT NULL,
    filiere_id INT Foreign Key (filiere_id) REFERENCES (filiere(id))
);
CREATE TABLE filiere (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(30),
    nb_etudiant INT NOT NULL
);
CREATE TABLE surveillant(
    nom VARCHAR(30) NOT NULL,
    duree

)
