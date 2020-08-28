#!/opt/anaconda3/bin/python

##
## Based on: https://github.com/kremerben/haiku_api
##

import random
import time
from typing import Tuple
import json
import cgi
import requests


DATAMUSE_FULL_APIBASE = "https://api.datamuse.com/words?md=sp&sp=s*"
DATAMUSE_APIBASE = "https://api.datamuse.com/words?md=sp"
DATAMUSE_LIMIT_ARG = "&max={}"
DATAMUSE_STARTSWITH_ARG = "&sp={}*"
LINE_SPACE = "\n"


def respond(err, res=None):
    print(res)  # adds the generated Haiku to the CloudWatch logs
    return {
        "statusCode": 400 if err else 200,
        "body": err.message if err else json.dumps(res),
        "headers": {"Content-Type": "application/json",},
    }


def lambda_handler(event, context):
    if not event or "queryStringParameters" not in event or not event["queryStringParameters"]:
        return respond(
            None,
            "Please add a keyword parameter ex: https://haiku.kremer.dev/?keyword=potato "
            "- starts_with parameter is optional ex: https://haiku.kremer.dev/?keyword=potato&starts_with=v",
        )
    qs_params = event["queryStringParameters"]

    if "keyword" not in qs_params:
        return respond(
            None,
            "Please add a keyword parameter ex: https://haiku.kremer.dev/?keyword=potato "
            "- starts_with parameter is optional ex: https://haiku.kremer.dev/?keyword=potato&starts_with=v",
        )

    keyword = qs_params["keyword"]

    if "starts_with" in qs_params:
        starts_with = qs_params["starts_with"][:1]
        print(f"The keyword is {keyword}, and starts_with letter is {starts_with}.")
    else:
        print(f"The keyword is {keyword}.")
        starts_with = ""

    pg = HaikuGenerator(word=keyword, starts_with=starts_with)

    return respond(None, pg.build_haiku())


class PoemGenerator:
    """ Parent Class that gathers all the words """

    def __init__(self, word: str = "", starts_with: str = ""):
        self.word = word
        self.starts_with = starts_with

        # prime the word lists
        self.nouns = self.get_nouns(word)
        self.verbs = self.get_verbs(word)
        self.adjectives = self.get_adjectives(word)
        # self.adverbs = self.get_adverbs(word)
        self.associated_words = self.get_associated_words(word)
        self.synonyms = self.get_synonyms(word)
        self.kindof_words = self.get_kindof_words(word)
        self.preceding_words = self.get_preceding_words(word)
        self.following_words = self.get_following_words(word)

        self.all_nouns = self.get_all_nouns()
        self.all_verbs = self.get_all_verbs()
        self.all_adjectives = self.get_all_adjectives()
        self.all_adverbs = self.get_all_adverbs()

    def request_words(self, url, starts_with: str = "") -> list:
        """
            Simple request generator with limit
            :param url: request url
            :param starts_with: retrieve only words that start with this letter
            Return:
                [{'word': 'level', 'score': 31451, 'numSyllables': 2},
                 {'word': 'water', 'score': 16450, 'numSyllables': 2}]
        """
        if starts_with or self.starts_with:
            url += DATAMUSE_STARTSWITH_ARG.format(starts_with or self.starts_with)

        return requests.get(url).json()

    def get_related_words(self, word: str) -> list:
        """ Returns related words with syllable count

            Arg: word == string
            Return:
                [{'word': 'level', 'score': 31451, 'numSyllables': 2},
                 {'word': 'water', 'score': 16450, 'numSyllables': 2}]
        """
        related_words_url = f"{DATAMUSE_APIBASE}&ml={word}"
        return self.request_words(related_words_url)

    def get_nouns(self, word: str) -> list:
        return self.request_words(f"{DATAMUSE_APIBASE}&rel_jja={word}")

    def get_verbs(self, word: str) -> list:
        related_words = self.get_related_words(word)
        return [word for word in related_words if "tags" in word and "v" in word["tags"]]

    def get_adjectives(self, word: str) -> list:
        return self.request_words(f"{DATAMUSE_APIBASE}&rel_jjb={word}")

    def get_associated_words(self, word: str) -> list:
        """ Trigger words """
        self.associated_words = self.request_words(f"{DATAMUSE_APIBASE}&rel_trg={word}")
        return self.associated_words

    def get_synonyms(self, word: str) -> list:
        self.synonyms = self.request_words(f"{DATAMUSE_APIBASE}&rel_syn={word}")
        return self.synonyms

    def get_kindof_words(self, word: str) -> list:
        self.kindof_words = self.request_words(f"{DATAMUSE_APIBASE}&rel_spc={word}")
        return self.kindof_words

    def get_preceding_words(self, word: str) -> list:
        self.preceding_words = self.request_words(f"{DATAMUSE_APIBASE}&rel_bgb={word}")
        return self.preceding_words

    def get_following_words(self, word: str) -> list:
        self.following_words = self.request_words(f"{DATAMUSE_APIBASE}&rel_bga={word}")
        return self.following_words

    def indirectly_extend_word_lists(self, word_type_identifier="n"):
        extra_words = []
        extra_words.extend(self.associated_words)
        extra_words.extend(self.synonyms)
        extra_words.extend(self.kindof_words)
        extra_words.extend(self.preceding_words)
        extra_words.extend(self.following_words)
        return [
            word for word in extra_words if "tags" in word and word_type_identifier in word["tags"]
        ]

    def get_all_nouns(self) -> list:
        self.nouns.extend(self.indirectly_extend_word_lists("n"))
        return list({noun["word"]: noun for noun in self.nouns}.values())

    def get_all_verbs(self) -> list:
        self.verbs.extend(self.indirectly_extend_word_lists("v"))
        return list({verb["word"]: verb for verb in self.verbs}.values())

    def get_all_adjectives(self) -> list:
        self.adjectives.extend(self.indirectly_extend_word_lists("adj"))
        return list({adjective["word"]: adjective for adjective in self.adjectives}.values())

    def get_all_adverbs(self) -> list:
        adverbs = self.indirectly_extend_word_lists("adv")
        return list({adverb["word"]: adverb for adverb in adverbs}.values())


class HaikuGenerator(PoemGenerator):
    def build_haiku(self) -> Tuple[str, str, str]:

        haiku_syllables = [5, 7, 5]
        haiku_result = []

        current_wordtype = random.choice(["adj", "n", "v"])

        structure_mapping = {
            "n": {"next": "v", "wordlist": self.get_all_nouns()},
            "v": {
                "next": random.choices(["adj", "adv"], weights=[0.95, 0.05])[0],
                "wordlist": self.get_all_verbs(),
            },
            "adj": {
                "next": random.choices(["n", "adv"], weights=[0.95, 0.05])[0],
                "wordlist": self.get_all_adjectives(),
            },
            "adv": {
                "next": random.choices(["v", "adj"], weights=[0.8, 0.2])[0],
                "wordlist": self.get_all_adverbs(),
            },
        }

        used_words = []

        for syllable_count in haiku_syllables:
            syllable_target = syllable_count
            current_line = []
            error_count = 0

            while syllable_target > 0:
                error_count += 1

                current_words = [
                    _word
                    for _word in structure_mapping[current_wordtype]["wordlist"]
                    if ("numSyllables" in _word and _word["numSyllables"] <= syllable_target)
                ]
                word_to_add = random.choice(current_words) if current_words else None

                if word_to_add and word_to_add["word"] in used_words and error_count < 10:
                    # minimize duplicates please
                    continue

                elif word_to_add:
                    used_words.append(word_to_add["word"])
                    current_line.append(word_to_add["word"])
                    syllable_target -= word_to_add["numSyllables"]

                current_wordtype = structure_mapping[current_wordtype]["next"]

            haiku_result.append(current_line)

        return " ".join(haiku_result[0]), " ".join(haiku_result[1]), " ".join(haiku_result[2])


def main():
    """ To run this python script locally:
    format:  $ python3 haiku_generator.py <keyword> <letter>
    example: $ python3 haiku_generator.py carrot m
    """
    import sys
    
    keyword = ""
    startswith = ""
    
    fs = cgi.FieldStorage()
    for key in fs.keys():
        if key == "keyword":
            keyword = fs[key].value
        if key == "starts_with":
            startswith = fs[key].value

    if keyword == "":
        print("Haiku generator usage: <keyword: required> <starts with letter: optional>")
        sys.exit(1)

    pg = HaikuGenerator(word=keyword, starts_with=startswith)

    print(json.dumps(pg.build_haiku()))


if __name__ == "__main__":
    print("Content-type: application/json\n")
    main()
