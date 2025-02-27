<?php

namespace App\Controller;

use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


#[Route('/crypto')]
final class CryptoController extends AbstractController
{
    /**
     * @throws \Exception
     */
    #[Route('', name: 'index')]
    public function index(Request $request): Response
    {
        $datePremiereTransaction = null;
        $dateDerniereTransaction = null;
        $totalInvesti = 0;
        $nombreTransationInvestissement = 0;
        $totalVendu = 0;
        $nombreTransationDesinvestissement = 0;
        $benefice = 0;
        $cryptos = [];
        $traitementFichier=false;
        $tauxImpositionCrypto = 0.3;
        $imposition=0;

        if ($request->isMethod('POST')) {

            // Récupération des données.
            /** @var UploadedFile $fichierKraken */
            $fichierKraken = $request->files->get('fichierCSV');


            if ($fichierKraken) {

                // Ouvrir le fichier en lecture
                $stream = fopen($fichierKraken->getPathname(), 'r');

                if ($stream !== false) {

                    // Lire et ignorer la première ligne
                    fgetcsv($stream);

                    while (($data = fgetcsv($stream, 0, ',')) !== false) {

                        /**
                         * Information Sur le fichier CSV de Kraken
                         * $data[0]→ "txid" ID unique de la transaction
                         * $data[2]→ "pair" paires consituant la transaction. Ex : SOL/USDT. Le premier actif est acheté/vendu, le second est utilisé pour le réglement.
                         * $data[4]→ "time" heure UTC
                         * $data[5]→ "type" type de transaction : Sell / Buy
                         * $data[7]→ "price" Le prix unitaire de l’actif lors de la transaction. Ex : 96118.80 est le prix d'un BTC.
                         * $data[8]→ "cost" C'est le montant total dépensé ou reçu pour la transaction. Cost = volume X price
                         * $data[9]→ "fee" cout de la transaction
                         * $data[10]→ "vol" volume, Le nombre d'unités échangées de l'actif principal (le premier actif de la paire)
                         */

                        $IdTransaction = $data[0];
                        $pair = $data[2];
                        $dateTransaction = new DateTime($data[4]);
                        $type = $data[5];
                        $price = (float)$data[7];
                        $cost = (float)$data[8];
                        $fee = (float)$data[9];
                        $volume = $data[10];

                        //Récupération des dates pour créer la période.
                        if (empty($datePremiereTransaction) || $dateTransaction < $datePremiereTransaction) {
                            $datePremiereTransaction = $dateTransaction;
                        }
                        if (empty($dateDerniereTransaction) || $dateTransaction > $dateDerniereTransaction) {
                            $dateDerniereTransaction = $dateTransaction;
                        }

                        //création d'un tableau pour les transactions effectuées
                        if (str_ends_with($pair, "/EUR")) {

                            $nomCrypto = strtok($pair, "/");

                            //Création de la crypto si elle n'existe pas encore dans le tableau.
                            if (!isset($cryptos[$nomCrypto])) {
                                $cryptos[$nomCrypto] = [
                                    "nombreDeTransaction" => 0,
                                    "totalInvesti" => 0,
                                ];

                            }

                            //mise à jour de la crypto avec les valeurs de la transaction.
                            $cryptos[$nomCrypto]["nombreDeTransaction"]++;
                            $cryptos[$nomCrypto]["totalInvesti"] += $cost;

                        }


                        //récupération du total investi
                        if (str_ends_with($pair, "/EUR") && $type == "buy") {
                            $nombreTransationInvestissement++;
                            $totalInvesti += $cost;
                        }

                        //récupération du total vendu
                        if (str_ends_with($pair, "/EUR") && $type == "sell") {
                            $nombreTransationDesinvestissement++;
                            $totalVendu += $cost;
                        }

                        //calcul du bénéfice
                        $benefice = $totalVendu - $totalInvesti;

                        //calcul de l'imposition
                        $imposition = $benefice * $tauxImpositionCrypto;

                        $traitementFichier=true;

                    }

                    fclose($stream);
                } else {
                    throw new \Exception("Impossible d'ouvrir le fichier CSV.");
                }

            }
        }

        return $this->render('crypto/index.html.twig', [
            'traitementFichier'=>$traitementFichier,
            'premiereTransaction' => $datePremiereTransaction,
            'derniereTransaction' => $dateDerniereTransaction,
            'totalInvesti' => $totalInvesti,
            'nombreTransactionInvestissement' => $nombreTransationInvestissement,
            'totalVendu' => $totalVendu,
            'nombreTransactionDesinvestissement' => $nombreTransationDesinvestissement,
            'benefice' => $benefice,
            'cryptos' => $cryptos,
            'imposition' => $imposition,
        ]);
    }

}
