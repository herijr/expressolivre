CPPFLAGS = -O2
LDFLAGS = -O2
LDLIBS = -lz -lboost_system -lboost_filesystem

SRCs = $(shell ls *.cpp)
OBJs = $(SRCs:.cpp=.o)
EXE = zip

all: $(EXE)

$(EXE): $(OBJs)
	$(CXX) $(LDFLAGS) $(OBJs) -o $@ $(LDLIBS)

%.o: %.cpp %.h
	$(CXX) $(CFLAGS) $(CPPFLAGS) -c $< -o $@

clean:
	rm -f $(OBJs) $(EXE)
