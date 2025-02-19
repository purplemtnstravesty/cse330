import sys
import re

class Player:
    def __init__(self, name):
        self.name = name
        self.at_bats = 0
        self.hits = 0
        self.runs = 0   

    def update(self, at_bats, hits, runs):
        self.at_bats += int(at_bats)
        self.hits += int(hits)
        self.runs += int(runs)

    def batting_average(self):
        return round(self.hits / self.at_bats, 3) if self.at_bats > 0 else 0.0


def main():
    if len(sys.argv) < 2:
        print("Usage: python script.py <file_path>")
        sys.exit(1)

    file_path = sys.argv[1]
    players = {}

    # Corrected regex pattern with proper named groups
    pattern = re.compile(r"""
        ^(?P<name>[A-Za-z\.\'\- ]+)\s+batted\s+
        (?P<at_bats>\d+)\s+times\s+
        with\s+(?P<hits>\d+)\s+hits\s+
        and\s+(?P<runs>\d+)\s+runs
        """, re.IGNORECASE | re.VERBOSE)

    try:
        with open(file_path, "r") as f:
            for line in f:
                line = line.strip()
                match = pattern.match(line)
                if match:
                    data = match.groupdict()
                    name = data["name"].strip()
                    if name not in players:
                        players[name] = Player(name)
                    players[name].update(data["at_bats"], data["hits"], data["runs"])
    except IOError:
        print(f"Error reading file: {file_path}")
        sys.exit(1)

    # Output sorted by batting average in descending order
    for player in sorted(players.values(), key=lambda p: p.batting_average(), reverse=True):
        print(f"{player.name}: {player.batting_average():.3f}")


if __name__ == "__main__":
    main()
