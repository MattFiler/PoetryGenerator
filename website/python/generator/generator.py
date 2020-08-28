#!/opt/anaconda3/bin/python

##
## Based on: https://www.kaggle.com/paultimothymooney/poetry-generator-rnn-markov
##

print("Content-type: application/json\n")

import pronouncing
import markovify
import re
import random
import numpy as np
import os
os.environ['TF_CPP_MIN_LOG_LEVEL'] = '3' 
import keras
import cgi
from datetime import datetime
import json
from keras.models import Sequential
from keras.layers import LSTM 
from keras.layers.core import Dense

def create_network(depth):
	model = Sequential()
	model.add(LSTM(4, input_shape=(2, 2), return_sequences=True))
	for i in range(depth):
		model.add(LSTM(8, return_sequences=True))
	model.add(LSTM(2, return_sequences=True))
	model.compile(optimizer='rmsprop', loss='mse')
	model.load_weights(str(artist + ".rap"))
	return model
	
def markov(text_file):
	read = open(text_file, "r", encoding='utf-8').read()
	text_model = markovify.NewlineText(read)
	return text_model
	
def syllables(line):
	count = 0
	for word in line.split(" "):
		vowels = 'aeiouy'
		word = word.lower().strip(".:;?!")
		if len(word) == 0:
			continue
		if word[0] in vowels:
			count +=1
		for index in range(1,len(word)):
			if word[index] in vowels and word[index-1] not in vowels:
				count +=1
		if word.endswith('e'):
			count -= 1
		if word.endswith('le'):
			count+=1
		if count == 0:
			count +=1
	return count / maxsyllables
	
def rhymeindex(lyrics):
	return open(str(artist) + ".rhymes", "r",encoding='utf-8').read().split("\n")
		
def rhyme(line, rhyme_list):
	word = re.sub(r"\W+", '', line.split(" ")[-1]).lower()
	rhymeslist = pronouncing.rhymes(word)
	rhymeslistends = []
	for i in rhymeslist:
		rhymeslistends.append(i[-2:])
	try:
		rhymescheme = max(set(rhymeslistends), key=rhymeslistends.count)
	except Exception:
		rhymescheme = word[-2:]
	try:
		float_rhyme = rhyme_list.index(rhymescheme)
		float_rhyme = float_rhyme / float(len(rhyme_list))
		return float_rhyme
	except Exception:
		float_rhyme = None
		return float_rhyme
		
def split_lyrics_file(text_file):
	text = open(text_file, encoding='utf-8').read()
	text = text.split("\n")
	while "" in text:
		text.remove("")
	return text
	
def generate_lyrics(text_model, text_file):
	bars = []
	last_words = []
	lyriclength = len(open(text_file,encoding='utf-8').read().split("\n"))
	count = 0
	markov_model = markov(text_file)
	
	start_time = datetime.now()
	while len(bars) < num_of_lines:
		if (datetime.now()-start_time).total_seconds() > loop_timeout:
			break
		bar = markov_model.make_sentence(max_overlap_ratio = .49, tries=100)
		if type(bar) != type(None) and syllables(bar) < 1:
			def get_last_word(bar):
				last_word = bar.split(" ")[-1]
				if last_word[-1] in "!.?,":
					last_word = last_word[:-1]
				return last_word
			last_word = get_last_word(bar)
			if bar not in bars and last_words.count(last_word) < 3:
				bars.append(bar)
				last_words.append(last_word)
				count += 1
	return bars
	
def compose_rap(lines, rhyme_list, lyrics_file, model):
	rap_vectors = []
	human_lyrics = split_lyrics_file(lyrics_file)
	initial_index = random.choice(range(len(human_lyrics) - 1))
	initial_lines = human_lyrics[initial_index:initial_index + 2]
	starting_input = []
	for line in initial_lines:
		starting_input.append([syllables(line), rhyme(line, rhyme_list)])
	starting_vectors = model.predict(np.array([starting_input]).flatten().reshape(1, 2, 2))
	rap_vectors.append(starting_vectors)
	for i in range(100):
		rap_vectors.append(model.predict(np.array([rap_vectors[-1]]).flatten().reshape(1, 2, 2)))
	return rap_vectors
	
def vectors_into_song(vectors, generated_lyrics, rhyme_list):
	def last_word_compare(rap, line2):
		penalty = 0 
		for line1 in rap:
			word1 = line1.split(" ")[-1]
			word2 = line2.split(" ")[-1]
			while word1[-1] in "?!,. ":
				word1 = word1[:-1]
			while word2[-1] in "?!,. ":
				word2 = word2[:-1]
			if word1 == word2:
				penalty += 0.2
		return penalty
	def calculate_score(vector_half, syllables, rhyme, penalty):
		desired_syllables = vector_half[0]
		desired_rhyme = vector_half[1]
		desired_syllables = desired_syllables * maxsyllables
		desired_rhyme = desired_rhyme * len(rhyme_list)
		score = 1.0 - abs(float(desired_syllables) - float(syllables)) + abs(float(desired_rhyme) - float(rhyme)) - penalty
		return score
	dataset = []
	for line in generated_lyrics:
		line_list = [line, syllables(line), rhyme(line, rhyme_list)]
		dataset.append(line_list)
	rap = []
	vector_halves = []
	for vector in vectors:
		vector_halves.append(list(vector[0][0])) 
		vector_halves.append(list(vector[0][1]))
	for vector in vector_halves:
		scorelist = []
		for item in dataset:
			line = item[0]
			if len(rap) != 0:
				penalty = last_word_compare(rap, line)
			else:
				penalty = 0
			total_score = calculate_score(vector, item[1], item[2], penalty)
			score_entry = [line, total_score]
			scorelist.append(score_entry)
		fixed_score_list = [0]
		for score in scorelist:
			fixed_score_list.append(float(score[1]))
		max_score = max(fixed_score_list)
		for item in scorelist:
			if item[1] == max_score:
				rap.append(item[0])
				for i in dataset:
					if item[0] == i[0]:
						dataset.remove(i)
						break
				break	 
	return rap
	
def main(depth):
	model = create_network(depth)
	text_model = markov(text_file)
	bars = generate_lyrics(text_model, text_file)
	rhyme_list = rhymeindex(bars)
	vectors = compose_rap(bars, rhyme_list, text_file, model)
	rap = vectors_into_song(vectors, bars, rhyme_list)
	print(json.dumps(rap))

if __name__ == "__main__":
	artist = "shakespear"
	depth = 5
	maxsyllables = 8
	num_of_lines = 5
	loop_timeout = 60
	fs = cgi.FieldStorage()
	for key in fs.keys():
		if key == "artist":
			artist = fs[key].value.replace("%20", " ")
		if key == "num_of_lines":
			num_of_lines = int(fs[key].value)
		if key == "loop_timeout":
			loop_timeout = int(fs[key].value)
	text_file = "input/" + artist + ".txt" 
	main(depth)