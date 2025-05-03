# Schéma de Base de Données Nova Bookstore

Ce document décrit le schéma relationnel de la base de données utilisée pour l'application Nova Bookstore.

## Tables

1. **USERS**: Stocke les informations des utilisateurs du site.
2. **CATEGORIES**: Contient les différentes catégories de livres.
3. **BOOKS**: Stocke tous les livres disponibles à la vente.
4. **CARTS**: Définit les paniers des utilisateurs.
5. **CART_ITEMS**: Stocke les articles dans les paniers utilisateurs.
6. **ORDERS**: Contient les commandes passées par les utilisateurs.
7. **ORDER_ITEMS**: Stocke les articles dans les commandes.
8. **CANCELLED_ORDERS**: Historique des commandes annulées.

## Relations

- Un utilisateur peut avoir plusieurs commandes (1:N)
- Un utilisateur peut avoir un seul panier actif (1:1)
- Un panier peut contenir plusieurs articles (1:N)
- Une commande contient plusieurs articles (1:N)
- Un livre appartient à une catégorie (N:1)
- Une commande annulée correspond à une commande spécifique (1:1)

## Procédures Stockées

1. **get_order_details**: Affiche les détails d'une commande et calcule le total à payer
2. **finalize_order**: Finalise une commande et vide le panier
3. **get_user_orders**: Affiche l'historique des commandes d'un utilisateur

## Triggers

1. **update_stock**: Met à jour automatiquement le stock après la validation d'une commande
2. **check_stock**: Empêche l'insertion d'une commande si la quantité demandée dépasse le stock
3. **restore_stock_cancel**: Restaure le stock après l'annulation d'une commande et garde une trace dans la table CANCELLED_ORDERS

## Schéma ER
