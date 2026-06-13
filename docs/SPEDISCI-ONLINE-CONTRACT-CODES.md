# Contratti Spedisci.online (contractCode API)

Generato il: **2026-05-27 21:23**

## Origine dati

Elenco ottenuto chiamando l'API REST del tenant **quick** (quicksrl):

| | |
|---|---|
| **Metodo** | `GET` |
| **URL** | `https://quicksrl.spedisci.online/api/v2/carriers` |
| **Auth** | `Authorization: Bearer <SPEDISCI_ONLINE_API_KEY>` |
| **Documentazione** | [apidocs.spedisci.online](https://apidocs.spedisci.online/) — per `/shipping/rates` e `/pickup/create` il `contractCode` deve essere un contratto associato all'account |
| **Codice portale** | `App\Services\SpedisciOnline\SpedisciOnlineCarriersService` (cache 1h) — usato nella pagina `/test/spedisci-online` |

Nel **pannello web** compaiono nomi leggibili (es. vettore *PosteDeliveryBusiness*, contratto *PDB Multi*).
Nelle chiamate API si usano `carrierCode` (vettore) e `contractCode` (contratto) come nella tabella sotto.

> L'elenco dipende dall'**API key** in `.env` (`SPEDISCI_ONLINE_API_KEY`). Un altro tenant (es. liccardi) può avere contratti diversi.

## Esempio (Poste PDB Multi)

| Pannello | API |
|----------|-----|
| Vettore: PosteDeliveryBusiness | `carrierCode`: `postedeliverybusiness` |
| Contratto: PDB Multi | `contractCode`: `TPEp4Ph7OzIRWtTL` |

---

## Gls (`carrierCode`: `gls`)

| Nome contratto (pannello) | contractCode (API) |
|---------------------------|--------------------|
| Q GLS CE LIGHT | `gls-Q-GLS-CE-LIGHT` |
| Q GLS CE STANDARD | `gls-Q-GLS-CE-STANDARD` |
| Q GLS CK LIGHT | `gls-Q-GLS-CK-LIGHT` |
| Q GLS CK STANDARD | `gls-Q-GLS-CK-STANDARD` |
| Q GLS NL LIGHT | `gls-Q-GLS-NL-LIGHT` |
| Q GLS NL STANDARD | `gls-Q-GLS-NL-STANDARD` |
| Q GLS NN LIGHT | `gls-Q-GLS-NN-LIGHT` |
| Q GLS NN STANDARD | `gls-Q-GLS-NN-STANDARD` |
| NN INTERNAZIONALE | `gls-NN-INTERNAZIONALE` |
| GLS CK PLATINUM | `NGFJLPZC6JFqeuDz` |
| GLS 5000 | `QKOwWRlo5GlSWSHb` |
| GLS CK EUROPA | `b7qSmiztFY6jPfgu` |
| GLS 5000 EUROPA | `zrH7wwt1w8UncqwW` |
| PARCELFLOW GLS NN | `WJAfzTai4SiZmpRs` |
| PARCELFLOW GLS NL | `1xpugJsrcx8DUb0h` |
| GLS CE PLATINUM | `ZoduL23DI6oQyP7P` |
| PARCELFLOW CK | `lAn4AhY3STHnKpbB` |
| PARCELFLOW CK LIGHT | `eUyAB04lD1B0mhvV` |
| GLS CE ORO | `hHK7yf5lqHi0yCsY` |
| GLS NN LIGHT MULTI | `Yeckk6Or8zoAJanp` |
| GLS NN MULTI | `oR09CIC5i7ZFXItB` |
| GLS NL LIGHT MULTI | `5ij0ZOHS7ibjAcvo` |
| GLS NL MULTI | `7J5jlSKb9dqcoyy3` |

## Sda (`carrierCode`: `sda`)

| Nome contratto (pannello) | contractCode (API) |
|---------------------------|--------------------|
| SDA EXPRESS | `1mI91tNHRP00t25J` |
| SDA Multi | `5S74q7Xlrk95lZdZ` |

## Interno (`carrierCode`: `interno`)

| Nome contratto (pannello) | contractCode (API) |
|---------------------------|--------------------|
| QUICK EXPRESS | `interno-mondoexpress` |

## Tnt (`carrierCode`: `tnt`)

| Nome contratto (pannello) | contractCode (API) |
|---------------------------|--------------------|
| TNT ITALIA | `tnt-TNT-ITALIA` |
| TNT SCS | `tnt-TNT-SCS` |
| TNT EXPRESS 12 | `tnt-TNT-EXPRESS-12` |

## Brt (`carrierCode`: `brt`)

| Nome contratto (pannello) | contractCode (API) |
|---------------------------|--------------------|
| BRT UFFICIALE | `brt-BRT-UFFICIALE` |
| BRT SALERNO | `brt-BRT-SALERNO` |
| DPD MULTICOLLO | `brt-DPD-MULTICOLLO` |
| PARCEL BRT | `brt-PARCEL-BRT` |
| BRT EXPRESS | `brt-BRT-EXPRESS` |
| BARTOLINI | `brt-BARTOLINI` |
| BRT (no Campania) | `lwIkxM6MdIE81PSA` |
| BRT PF | `IGA00AOTlPiC9tQJ` |
| DPD PF | `8IJjcNCWSmvJ6oXI` |

## PosteDeliveryBusiness (`carrierCode`: `postedeliverybusiness`)

| Nome contratto (pannello) | contractCode (API) |
|---------------------------|--------------------|
| PDB EXPRESS (12) | `postedeliverybusiness-PDB-EXPRESS` |
| POSTE DELIVERY BUSINESS | `postedeliverybusiness-POSTE-DELIVERY-BUSINESS` |
| PDB DROP | `postedeliverybusiness-PDB-DROP` |
| PDB H24 | `zEbdyGq6cJkAF7lI` |
| PDB Multi | `TPEp4Ph7OzIRWtTL` |

## Fedex (`carrierCode`: `fedex`)

| Nome contratto (pannello) | contractCode (API) |
|---------------------------|--------------------|
| FEDEX ITALIA | `fedex-FEDEX-ITALIA` |
| FEDEX ICP | `fedex-FEDEX-EUROPA` |

## AmazonShipping (`carrierCode`: `amazonshipping`)

| Nome contratto (pannello) | contractCode (API) |
|---------------------------|--------------------|
| 5769159308 | `5769159308` |

## UPS (`carrierCode`: `ups`)

| Nome contratto (pannello) | contractCode (API) |
|---------------------------|--------------------|
| UPS EUROPA | `73WL4niZOYqk2bLw` |
| UPS STANDARD EUROPA | `vmAFWORMsBh9rXNw` |

---

**Totale contratti:** 48
