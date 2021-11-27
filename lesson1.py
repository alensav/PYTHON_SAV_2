#!/usr/bin/python3.6
import math
#Определяем функцию:
def Square (radius): #определим Square с аргументом  radius
    return math.pi * radius**2 #взвращаем "пи эр квадрат"
#Проверяем  работу функции:
print ("Площадь круга радиуса", 2, "равняется", Square(2))
print ("Площадь круга радиуса", 4, "равняется", Square(4))


