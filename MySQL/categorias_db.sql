CREATE DATABASE tp_categorias;
 
USE tp_categorias;
 
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(60) DEFAULT NULL,
  `apellido` varchar(60) DEFAULT NULL,
  `usuario` varchar(50) DEFAULT NULL,
  `email` varchar(50) NOT NULL,
  `password_hash` text NOT NULL,
  `api_key` varchar(32) NOT NULL,
  `status` int(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `email` (`email`),
  UNIQUE key `usuario` (`usuario`)
);
 
CREATE TABLE IF NOT EXISTS `categorias` (
  `id_categoria` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `titulo` varchar(50) NOT NULL ,
  `descripcion` varchar(50) NOT NULL ,
  `url_foto` varchar(50) NOT NULL ,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_categoria`),
  FOREIGN KEY (  `id_usuario` ) REFERENCES  `tp_categorias`.`usuario` (`id_usuario`) 
  ON DELETE CASCADE ON UPDATE CASCADE
  );

 
